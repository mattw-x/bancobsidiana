<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Merchant; // Importante: Importamos el modelo Merchant
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function process(Request $request)
    {
        // 1. Validación de la petición entrante del comercio (POS)
        $validator = Validator::make($request->all(), [
            'card_number'         => 'required|string',
            'cvv'                 => 'required|string',
            'expiry'              => 'required|string',
            'amount'              => 'required|numeric|min:0.01',
            'description'         => 'nullable|string|max:255',
            // Reemplazamos destination_account por merchant_id según el protocolo del Sprint 3
            'merchant_id'         => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'ERROR', 'errors' => $validator->errors()], 422);
        }

        // Limpiar el número de tarjeta y extraer el BIN (primeros 2 dígitos)
        $cardNumberClean = str_replace(' ', '', $request->card_number);
        $bin = substr($cardNumberClean, 0, 2);

        $amount = $request->amount;
        $merchantId = $request->merchant_id; // Ej: "ciens-mart" o "t3ch-pr0"
        $description = $request->description ?? 'Compra en comercio';

        // -------------------------------------------------------------------
        // 2. ENRUTAMIENTO (ROUTING) SEGÚN EL BIN DE LA TARJETA
        // -------------------------------------------------------------------
        switch ($bin) {
            case '05':
                // TARJETA LOCAL (BANCOBSIDIANA)
                return $this->processLocalTransaction($cardNumberClean, $request->cvv, $request->expiry, $amount, $description, $merchantId);

            case '46':
                // BANCO EXTERNO 1 (CIENSpay - Equipo 2/5)
                return $this->processBank2Transaction($request->card_number, $request->cvv, $request->expiry, $amount, $description, $merchantId);

            case '52':
                // BANCO EXTERNO 2 (Core Banking - Equipo 3)
                return $this->processBank3Transaction($request->card_number, $request->cvv, $request->expiry, $amount, $description, $merchantId);

            default:
                // BIN DESCONOCIDO
                return response()->json([
                    'status' => 'DECLINED',
                    'message' => "BIN '$bin' no reconocido. No existe ruta de adquirencia para este banco."
                ], 402); // 402 Payment Required
        }
    }

    // =======================================================================
    // MÉTODOS PRIVADOS DE PROCESAMIENTO
    // =======================================================================

    private function processLocalTransaction($cardNumber, $cvv, $expiry, $amount, $description, $merchantId)
    {
        DB::beginTransaction();
        try {
            $card = Card::where('card_number', $cardNumber)->with('account.user')->first();

            // Validaciones de seguridad e integridad
            if (!$card) return response()->json(['status' => 'DECLINED', 'message' => 'Tarjeta inexistente'], 404);
            if ($card->cvv !== $cvv || $card->expiration_date->format('m/y') !== $expiry) {
                return response()->json(['status' => 'DECLINED', 'message' => 'Datos de tarjeta inválidos'], 402);
            }

            $fee = round($amount * 0.02, 2); // 2% de comisión
            $totalToDebit = $amount + $fee;

            if ($card->account->balance < $totalToDebit) {
                return response()->json(['status' => 'DECLINED', 'message' => 'Fondos Insuficientes'], 402);
            }

            // A. Descontar al comprador local
            $card->account->decrement('balance', $totalToDebit);

            // B. Acreditar al comercio usando su merchant_id
            $this->creditLocalMerchant($merchantId, $amount, "Venta local: $description");

            // C. Registrar transacción de compra
            $transaction = Transaction::create([
                'card_id'          => $card->id,
                'account_id'       => $card->account->id,
                'merchant_name'    => $merchantId,
                'reference'        => 'OBS-' . strtoupper(bin2hex(random_bytes(4))),
                'type'             => 'purchase',
                'amount'           => $amount,
                'fee'              => $fee,
                'status'           => 'approved',
                'response_code'    => '00',
                'response_message' => "Transaccion exitosa"
            ]);

            DB::commit();

            return response()->json([
                'status' => 'APPROVED',
                'auth_code' => $transaction->reference,
                'message' => 'Transaccion exitosa'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'ERROR', 'message' => 'Error interno del servidor'], 500);
        }
    }

    private function processBank2Transaction($cardNumber, $cvv, $expiry, $amount, $description, $merchantId)
    {
        // Formatear JSON estrictamente como lo pide la API de CIENSpay (Caso 2)
        $payload = [
            'amount'               => (string) $amount, //
            // IMPORTANTE: El documento dice que esto identifica al banco EMISOR.
            // Como estamos enrutando hacia ellos, ellos son el emisor.
            'bank_identifier'      => 'cienspay', //
            // button_bank_external en true porque el comercio NO es de CiensPay, es nuestro[cite: 791, 800].
            'button_bank_external' => true, //
            'card_number'          => $cardNumber, //
            'cvv'                  => $cvv, //
            'description'          => $description, //
            'expiry_date'          => $expiry //
        ];

        try {
            $response = Http::timeout(15)->post('http://3.144.142.161/api/transactions/simulate/', $payload); // [cite: 757]
            $data = $response->json();

            // Verificamos HTTP 200 y que su JSON diga success: true [cite: 802, 803]
            if ($response->successful() && isset($data['success']) && $data['success'] === true) {

                // ¡Aprobado! El emisor (CiensPay) descontó a su cliente.
                // Nosotros (BancObsidiana) le depositamos a nuestro comercio.
                $this->creditLocalMerchant($merchantId, $amount, "Liquidación externa CIENSpay");

                // Registrar el enrutamiento para auditoría
                $this->logRoutingTransaction($merchantId, '46', $amount, 'CIENSpay');

                return response()->json([
                    'status' => 'ROUTING',
                    'auth_code' => 'EXT-' . strtoupper(bin2hex(random_bytes(4))),
                    'message' => 'Transaccion enrutada a red externa',
                    'external_data' => $data
                ], 200);
            }

            // Manejo de errores basado en la tabla de respuestas de CiensPay (ej. "Fondos insuficientes")
            $errorMessage = $data['message'] ?? 'Rechazado por el banco emisor (CIENSpay)';
            return response()->json(['status' => 'DECLINED', 'message' => $errorMessage, 'details' => $data], 402);

        } catch (\Exception $e) {
            // Manejo del error 502 especificado en la documentación
            return response()->json(['status' => 'ERROR', 'message' => 'Error en comunicación bancaria con CIENSpay'], 502);
        }
    }

    private function processBank3Transaction($cardNumber, $cvv, $expiry, $amount, $description, $merchantId)
    {
        $payload = [
            'card_number' => $cardNumber,
            'expiry_date' => $expiry,
            'cvv'         => $cvv,
            'amount'      => (float) $amount,
            'description' => $description
        ];

        try {
            $response = Http::timeout(15)->post('https://core-banking-service-6pup.onrender.com/api/external/verify-and-charge', $payload);
            $data = $response->json();

            if ($response->successful() && isset($data['status']) && $data['status'] === 'approved') {
                // ¡Aprobado! Depositamos a la cuenta local del comercio
                $this->creditLocalMerchant($merchantId, $amount, "Liquidación externa CoreBank");

                $this->logRoutingTransaction($merchantId, '52', $amount, 'Core Banking');

                return response()->json([
                    'status' => 'ROUTING',
                    'auth_code' => 'EXT-' . strtoupper(bin2hex(random_bytes(4))),
                    'message' => 'Transaccion enrutada a red externa',
                    'external_data' => $data
                ], 200);
            }

            return response()->json(['status' => 'DECLINED', 'message' => 'Rechazado por el banco emisor (Core Banking)'], 402);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => 'Error de conexión con Core Banking'], 502);
        }
    }

    /**
     * Función Helper: Abona los fondos procesados a la cuenta bancaria del comercio local.
     * MODIFICADO: Ahora descuenta el fee y asocia el movimiento a la tarjeta del comercio.
     */
    private function creditLocalMerchant($merchantId, $amount, $description, $feeAmount = 0)
    {
        if (!$merchantId) return;

        try {
            $merchant = Merchant::where('merchant_name', $merchantId)->first();

            if ($merchant && $merchant->card_id) {
                // Buscamos la tarjeta vinculada al comercio
                $card = Card::find($merchant->card_id);

                if ($card && $card->account) {
                    $account = $card->account;

                    // El comercio recibe el monto MENOS la comisión
                    $netAmount = $amount - $feeAmount;
                    $account->increment('balance', $netAmount);

                    // Se registra el movimiento ASOCIADO A LA TARJETA del comercio
                    Transaction::create([
                        'card_id'          => $card->id, // Esto hace que se vea en el dashboard del comercio
                        'account_id'       => $account->id,
                        'merchant_name'    => 'LIQUIDACIÓN: ' . $merchantId,
                        'reference'        => 'DEP-' . strtoupper(bin2hex(random_bytes(4))),
                        'type'             => 'deposit',
                        'amount'           => $netAmount,
                        'fee'              => $feeAmount, // Registramos el fee que se le cobró
                        'status'           => 'approved',
                        'response_code'    => '00',
                        'response_message' => $description
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error acreditando saldo al comercio {$merchantId}: " . $e->getMessage());
        }
    }


    /**
     * NUEVO MÉTODO: Transferencias Directas a Número de Cuenta
     */
    public function transferToAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_number'    => 'required|string',
            'cvv'            => 'required|string',
            'expiry'         => 'required|string',
            'target_account' => 'required|string', // Número de cuenta destino
            'amount'         => 'required|numeric|min:1',
            'description'    => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'ERROR', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Validar emisor
            $cardNumberClean = str_replace(' ', '', $request->card_number);
            $sourceCard = Card::where('card_number', $cardNumberClean)->with('account')->first();

            if (!$sourceCard || $sourceCard->cvv !== $request->cvv || $sourceCard->expiration_date->format('m/y') !== $request->expiry) {
                return response()->json(['status' => 'DECLINED', 'message' => 'Credenciales inválidas'], 402);
            }

            if ($sourceCard->account->balance < $request->amount) {
                return response()->json(['status' => 'DECLINED', 'message' => 'Fondos Insuficientes'], 402);
            }

            // 2. Validar destino
            $targetAccount = Account::where('account_number', $request->target_account)->with('cards')->first();
            if (!$targetAccount) {
                return response()->json(['status' => 'DECLINED', 'message' => 'Cuenta destino no encontrada'], 404);
            }

            // 3. Ejecutar Transferencia (Sin comisiones por ser P2P local)
            $sourceCard->account->decrement('balance', $request->amount);
            $targetAccount->increment('balance', $request->amount);

            $ref = 'TRF-' . strtoupper(bin2hex(random_bytes(4)));

            // 4. Log para el Emisor (Débito)
            Transaction::create([
                'card_id'       => $sourceCard->id,
                'account_id'    => $sourceCard->account->id,
                'merchant_name' => 'Transferencia Enviada a ' . $targetAccount->account_number,
                'reference'     => $ref,
                'type'          => 'transfer_out',
                'amount'        => -$request->amount,
                'fee'           => 0,
                'status'        => 'approved',
                'response_code' => '00',
            ]);

            // 5. Log para el Destinatario (Crédito)
            // Se asocia a la primera tarjeta activa de la cuenta para que se vea en el dashboard
            $targetCard = $targetAccount->cards->where('status', 'active')->first();

            Transaction::create([
                'card_id'       => $targetCard ? $targetCard->id : null,
                'account_id'    => $targetAccount->id,
                'merchant_name' => 'Transferencia Recibida de ' . $sourceCard->account->account_number,
                'reference'     => $ref,
                'type'          => 'transfer_in',
                'amount'        => $request->amount,
                'fee'           => 0,
                'status'        => 'approved',
                'response_code' => '00',
            ]);

            DB::commit();
            return response()->json([
                'status' => 'APPROVED',
                'auth_code' => $ref,
                'message' => 'Transferencia realizada con éxito'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'ERROR', 'message' => 'Error al procesar transferencia'], 500);
        }
    }

    /**
     * Función Helper: Registra la operación de enrutamiento para trazabilidad e intercambio
     */
    private function logRoutingTransaction($merchantId, $bin, $amount, $bankName)
    {
        try {
            $merchant = Merchant::where('merchant_name', $merchantId)->first();

            Transaction::create([
                'merchant_id'       => $merchant ? $merchant->user_id : null,
                'external_card_bin' => $bin,
                'merchant_name'     => $merchantId,
                'reference'         => 'RTG-' . strtoupper(bin2hex(random_bytes(4))),
                'type'              => 'routed_outgoing',
                'amount'            => $amount,
                'fee'               => 0,
                'status'            => 'approved',
                'response_code'     => 'RT',
                'response_message'  => "Enrutamiento liquidado de $bankName"
            ]);
        } catch (\Exception $e) {
            Log::error("Error registrando log de enrutamiento: " . $e->getMessage());
        }
    }
}
