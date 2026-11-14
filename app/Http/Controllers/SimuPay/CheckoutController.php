<?php

namespace App\Http\Controllers\SimuPay;

use App\Http\Controllers\Controller;
use App\Models\SimuPayTransaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Page de paiement de SimuPay : l'habitant y « paie » avec son numéro de téléphone.
 * Comme on simule, il choisit aussi le résultat (succès, solde insuffisant, refus) pour tester chaque cas.
 */
class CheckoutController extends Controller
{
    public const OUTCOMES = [
        'success' => 'Paiement accepté',
        'insufficient_funds' => 'Solde insuffisant',
        'declined' => 'Paiement refusé',
    ];

    public function show(SimuPayTransaction $transaction): View|RedirectResponse
    {
        if (! $transaction->isPending()) {
            return redirect()->away($transaction->return_url);
        }

        return view('simupay.checkout', ['transaction' => $transaction, 'outcomes' => self::OUTCOMES]);
    }

    public function confirm(Request $request, SimuPayTransaction $transaction): RedirectResponse
    {
        if (! $transaction->isPending()) {
            return redirect()->away($transaction->return_url);
        }

        $data = $request->validate([
            'phone' => ['required', 'string', 'min:7', 'max:20'],
            'pin' => ['required', 'digits:4'],
            'outcome' => ['required', Rule::in(array_keys(self::OUTCOMES))],
        ], [
            'pin.digits' => 'Le code secret fait 4 chiffres.',
        ]);

        $succeeded = $data['outcome'] === 'success';
        $transaction->update([
            'payer_phone' => $data['phone'],
            'status' => $succeeded ? SimuPayTransaction::STATUS_SUCCEEDED : SimuPayTransaction::STATUS_FAILED,
            'failure_reason' => $succeeded ? null : $data['outcome'],
        ]);

        $transaction->update(['webhook_status' => $this->notifyMerchant($transaction)]);

        return redirect()->away($transaction->return_url);
    }

    /**
     * Envoie la confirmation (webhook) au marchand, signée avec le secret partagé.
     * Retourne le code HTTP de la réponse du marchand, ou null s'il était injoignable.
     */
    private function notifyMerchant(SimuPayTransaction $transaction): ?int
    {
        $body = json_encode([
            'id' => $transaction->public_id,
            'reference' => $transaction->merchant_reference,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'failure_reason' => $transaction->failure_reason,
        ]);
        $timestamp = (string) now()->timestamp;

        // Signature = HMAC-SHA256(« horodatage.corps », secret) : prouve que le message vient de SimuPay
        // et qu'il n'a pas été modifié. L'horodatage empêche de rejouer un vieux message.
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, (string) config('services.simupay.webhook_secret'));

        try {
            return Http::timeout(5)
                ->withHeaders([
                    'X-SimuPay-Timestamp' => $timestamp,
                    'X-SimuPay-Signature' => $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($transaction->callback_url)
                ->status();
        } catch (ConnectionException) {
            return null;
        }
    }
}
