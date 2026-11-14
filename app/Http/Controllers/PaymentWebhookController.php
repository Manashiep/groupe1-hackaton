<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Réception du webhook de SimuPay : « le paiement sp_… est réussi / a échoué ».
 *
 * Cette adresse est publique : n'importe qui peut y envoyer un message. On ne croit donc le message
 * que s'il est récent ET signé avec le secret que seuls SimuPay et CleanComores connaissent.
 */
class PaymentWebhookController extends Controller
{
    /** Au-delà de 5 minutes, un message est considéré comme rejoué (attaque par rejeu). */
    private const MAX_AGE_SECONDS = 300;

    public function __invoke(Request $request): JsonResponse
    {
        $timestamp = (string) $request->header('X-SimuPay-Timestamp');
        $signature = (string) $request->header('X-SimuPay-Signature');
        $body = $request->getContent(); // le corps brut, exactement tel qu'il a été signé

        // 1. Le message est-il récent ?
        if (! ctype_digit($timestamp) || abs(now()->timestamp - (int) $timestamp) > self::MAX_AGE_SECONDS) {
            return response()->json(['error' => 'Horodatage invalide.'], 401);
        }

        // 2. Vient-il vraiment de SimuPay, sans avoir été modifié ?
        $expected = hash_hmac('sha256', $timestamp.'.'.$body, (string) config('services.simupay.webhook_secret'));
        if (! hash_equals($expected, $signature)) {
            return response()->json(['error' => 'Signature invalide.'], 401);
        }

        // 3. Concerne-t-il un paiement que nous connaissons ?
        $payload = json_decode($body, true) ?? [];
        $payment = Payment::where('provider_id', $payload['id'] ?? null)->first();
        if (! $payment) {
            return response()->json(['error' => 'Paiement inconnu.'], 404);
        }

        // 4. On applique le résultat (sans effet si déjà fait : SimuPay peut renvoyer le même message).
        $payment->applyGatewayStatus((string) ($payload['status'] ?? ''), $payload['failure_reason'] ?? null);

        return response()->json(['received' => true]);
    }
}
