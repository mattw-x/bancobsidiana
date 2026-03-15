<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserPaymentController extends Controller
{
    public function pagoMovil(Request $request)
    {
        $request->validate([
            'target_card_id'    => 'required|exists:cards,id',
            'bank_identifier'   => 'required|in:cienspay,creditbank',
            'source_identifier' => 'required|string',
            'amount'            => 'required|numeric|min:1',
            'description'       => 'nullable|string',
        ]);

        $card = Card::with('account')->findOrFail($request->target_card_id);
        $bank = $request->bank_identifier;
        $amount = $request->amount;

        // 1. Intentar cobrar al banco externo (Simulación de API)
        $response = $this->callExternalBankApi($bank, $request->source_identifier, $amount);

        if ($response['status'] !== 'APPROVED') {
            return back()->with('error', 'La recarga fue rechazada por ' . strtoupper($bank) . ': ' . ($response['message'] ?? 'Error desconocido'));
        }

        // 2. Si el banco externo aprobó, sumamos el saldo localmente
        DB::beginTransaction();
        try {
            $card->account->increment('balance', $amount);

            // 3. Registrar la transacción para que aparezca en el Dashboard
            Transaction::create([
                'card_id'          => $card->id,
                'account_id'       => $card->account->id,
                'merchant_name'    => 'RECARGA PAGO MÓVIL: ' . strtoupper($bank),
                'reference'        => 'PM-' . strtoupper(bin2hex(random_bytes(4))),
                'type'             => 'deposit',
                'amount'           => $amount,
                'fee'              => 0,
                'status'           => 'approved',
                'response_code'    => '00',
                'response_message' => $request->description ?? 'Recarga exitosa'
            ]);

            DB::commit();
            return back()->with('success', "¡Recarga exitosa! Se han acreditado $" . number_format($amount, 2) . " a tu cuenta.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error procesando abono de Pago Móvil: " . $e->getMessage());
            return back()->with('error', 'Ocurrió un error interno al procesar tu saldo.');
        }
    }

    /**
     * Simulación de llamada a APIs externas (CIENSpay / CreditBank)
     * Aquí deberías colocar las URLs reales de tus compañeros o del simulador.
     */
    private function callExternalBankApi($bank, $source, $amount)
    {
        // Ejemplo de URLs (ajustar según sea necesario)
        $endpoints = [
            'cienspay'   => 'https://cienspay.example.com/api/external/pull',
            'creditbank' => 'https://creditbank.example.com/api/v1/transaction'
        ];

        // Payload dinámico según lo que pida cada banco
        $payload = [
            'source'      => $source,
            'amount'      => $amount,
            'currency'    => 'USD',
            'callback'    => route('home'), // Por si acaso piden retorno
            'description' => 'Pago Móvil a BancObsidiana'
        ];

        try {
            // En un entorno real usarías:
            // $res = Http::post($endpoints[$bank], $payload);
            // return $res->json();

            // SIMULACIÓN DE ÉXITO PARA PRUEBAS:
            return ['status' => 'APPROVED', 'message' => 'Transacción exitosa'];

        } catch (\Exception $e) {
            return ['status' => 'ERROR', 'message' => 'Conexión fallida con el banco'];
        }
    }
}
