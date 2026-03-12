<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class TransactionController extends Controller
{
    public function process(Request $request)
    {
        // 1. Validación de la petición entrante del comercio
        $validator = Validator::make($request->all(), [
            'card_number'         => 'required|string',
            'cvv'                 => 'required|string',
            'expiry'              => 'required|string',
            'amount'              => 'required|numeric|min:0.01',
            'description'         => 'nullable|string|max:255',
            'destination_account' => 'nullable|string', // Cuenta local en BancObsidiana que recibe el dinero
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'ERROR', 'errors' => $validator->errors()], 422);
        }

        // Limpiar el número de tarjeta y extraer el BIN (primeros 2 dígitos)
        $cardNumberClean = str_replace(' ', '', $request->card_number);
        $bin = substr($cardNumberClean, 0, 2);

        $amount = $request->amount;
        $destinationAccount = $request->destination_account;
        $description = $request->description ?? 'Compra en comercio';

        // -------------------------------------------------------------------
        // 2. ENRUTAMIENTO (ROUTING) SEGÚN EL BIN DE LA TARJETA
        // -------------------------------------------------------------------
        switch ($bin) {
            case '05':
                // TARJETA LOCAL (BANCOBSIDIANA)
                return $this->processLocalTransaction($cardNumberClean, $request->cvv, $request->expiry, $amount, $description, $destinationAccount);

            case '46':
                // BANCO EXTERNO 1 (CIENSpay - Equipo 2/5)
                return $this->processBank2Transaction($request->card_number, $request->cvv, $request->expiry, $amount, $description, $destinationAccount);

            case '52':
                // BANCO EXTERNO 2 (Core Banking - Equipo 3)
                return $this->processBank3Transaction($request->card_number, $request->cvv, $request->expiry, $amount, $description, $destinationAccount);

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

    private function processLocalTransaction($cardNumber, $cvv, $expiry, $amount, $description, $destinationAccountNum)
    {
        DB::beginTransaction();
        try {
            $card = Card::where('card_number', $cardNumber)->with('account.user')->first();

            // Validaciones de seguridad e integridad
            if (!$card) return response()->json(['status' => 'DECLINED', 'message' => 'Tarjeta inexistente'], 402);
            if ($card->cvv !== $cvv) return response()->json(['status' => 'DECLINED', 'message' => 'CVV Incorrecto'], 402);
            if ($card->expiration_date->format('m/y') !== $expiry) return response()->json(['status' => 'DECLINED', 'message' => 'Expiración inválida'], 402);

            $fee = round($amount * 0.02, 2); // 2% de comisión
            $totalToDebit = $amount + $fee;

            if ($card->account->balance < $totalToDebit) {
                return response()->json(['status' => 'DECLINED', 'message' => 'Saldo insuficiente'], 402);
            }

            // A. Descontar al comprador (tarjetahabiente)
            $card->account->decrement('balance', $totalToDebit);

            // B. Acreditar al comercio (si se especificó cuenta destino)
            $this->creditLocalMerchant($destinationAccountNum, $amount, "Venta local: $description");

            // C. Registrar
            $transaction = Transaction::create([
                'card_id'          => $card->id,
                'account_id'       => $card->account->id,
                'merchant_name'    => $description,
                'reference'        => 'OBS-' . strtoupper(bin2hex(random_bytes(4))),
                'type'             => 'PURCHASE',
                'amount'           => $amount,
                'fee'              => $fee,
                'status'           => 'approved',
                'response_message' => "Pago procesado exitosamente"
            ]);

            DB::commit();

            return response()->json([
                'status' => 'APPROVED',
                'auth'   => $transaction->reference,
                'client' => $card->account->user->name ?? 'Cliente Obsidiana'
            ], 200); // 200 OK

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'ERROR', 'message' => 'Error interno del servidor'], 500);
        }
    }

    private function processBank2Transaction($cardNumber, $cvv, $expiry, $amount, $description, $destinationAccountNum)
    {
        // Formatear JSON estrictamente como lo pide la API de CIENSpay
        $payload = [
            'amount'               => (string) $amount,
            'bank_identifier'      => 'bancobsidiana',
            'button_bank_external' => true,
            'card_number'          => $cardNumber,
            'cvv'                  => $cvv,
            'description'          => $description,
            'expiry_date'          => $expiry
        ];

        try {
            $response = Http::timeout(15)->post('http://3.144.142.161/api/transactions/simulate/', $payload);
            $data = $response->json();

            // Verificamos HTTP 200 y que su JSON diga success: true
            if ($response->successful() && isset($data['success']) && $data['success'] === true) {

                // ¡Aprobado! Nosotros como adquirentes depositamos el dinero en la cuenta de nuestro comercio
                $this->creditLocalMerchant($destinationAccountNum, $amount, "Liquidación CIENSpay: $description");

                return response()->json([
                    'status' => 'APPROVED',
                    'message' => 'Transacción externa aprobada por CIENSpay.',
                    'external_data' => $data
                ], 200);
            }

            return response()->json(['status' => 'DECLINED', 'message' => 'Rechazado por el banco emisor (CIENSpay)', 'details' => $data], 402);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => 'Error de conexión con CIENSpay'], 502);
        }
    }

    private function processBank3Transaction($cardNumber, $cvv, $expiry, $amount, $description, $destinationAccountNum)
    {
        // Formatear JSON estrictamente como lo pide Core Banking Service
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

            // Verificamos HTTP 200 y que su JSON diga status: "approved"
            if ($response->successful() && isset($data['status']) && $data['status'] === 'approved') {

                // ¡Aprobado! Depositamos en la cuenta de nuestro comercio local
                $this->creditLocalMerchant($destinationAccountNum, $amount, "Liquidación CoreBank: $description");

                return response()->json([
                    'status' => 'APPROVED',
                    'message' => 'Transacción externa aprobada por Core Banking.',
                    'external_data' => $data
                ], 200);
            }

            return response()->json(['status' => 'DECLINED', 'message' => 'Rechazado por el banco emisor (Core Banking)', 'details' => $data], 402);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => 'Error de conexión con Core Banking'], 502);
        }
    }

    /**
     * Función Helper: Si la petición trae una cuenta de destino de BancObsidiana,
     * se le abona el dinero, sin importar si la tarjeta origen es nuestra o externa.
     */
    private function creditLocalMerchant($accountNumber, $amount, $description)
    {
        if (!$accountNumber) return; // Si no envían cuenta destino, ignoramos

        try {
            // Buscamos la cuenta de nuestro cliente/comercio local
            $account = Account::where('account_number', $accountNumber)->first();

            if ($account) {
                $account->increment('balance', $amount); // Le sumamos el dinero

                // (Opcional) Guardamos un registro de liquidación
                Transaction::create([
                    'card_id'          => null, // No hay tarjeta local asociada, viene de afuera
                    'account_id'       => $account->id,
                    'merchant_name'    => 'LIQUIDACIÓN BANCARIA',
                    'reference'        => 'ACQ-' . strtoupper(bin2hex(random_bytes(4))),
                    'type'             => 'DEPOSIT',
                    'amount'           => $amount,
                    'fee'              => 0,
                    'status'           => 'approved',
                    'response_message' => $description
                ]);
            }
        } catch (\Exception $e) {
            // Manejar error de escritura local si es necesario
        }
    }
}
