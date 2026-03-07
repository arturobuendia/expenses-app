<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AiCategorizationService;
use App\Services\FinanceManagerService;
use App\Services\WhaasService;
use Illuminate\Support\Facades\Log;

class WhaasWebhookController extends Controller
{
    public function handle(
        Request $request,
        AiCategorizationService $ai,
        FinanceManagerService $finance,
        WhaasService $whaas
    ) {
        //TODO: Validar que el request venga de Whaas (token secreto o similar)
        $messageText = $request->input('message');
        $fromNumber = $request->input('from');

        if (!$messageText || !$fromNumber) {
            return response()->json(['error' => 'Payload inválido o incompleto'], 400);
        }

        try {
            // 1. Procesamos el texto con la IA
            $parsedData = $ai->categorize($messageText);

            // 2. Guardamos como Gasto o Ingreso (tablas separadas)
            $record = $finance->recordTransaction($parsedData);

            // 3. Armamos un mensaje de confirmación bonito
            $tipo = $parsedData['type'] === 'expense' ? 'Gasto' : 'Ingreso';
            $monto = number_format($record->amount, 2);
            $respuesta = "✅ {$tipo} de $\n{$monto} registrado en la categoría correcta. ({$record->description})";

            // 4. Te enviamos el WhatsApp de vuelta
            $whaas->sendMessage($fromNumber, $respuesta);

            return response()->json(['status' => 'success', 'data' => $record]);
        } catch (\Exception $e) {
            Log::error("Error en Webhook Whaas: " . $e->getMessage());

            $whaas->sendMessage($fromNumber, "❌ No pude procesar el mensaje. Intenta ser más claro con el monto y el concepto.");

            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }
}
