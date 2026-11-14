<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Due;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * Paiement de la redevance par l'habitant, via la passerelle SimuPay.
 *
 *   1. pay()     : EcoCom appelle l'API de SimuPay et envoie l'habitant sur la page de paiement.
 *   2. show()    : l'habitant revient ; on affiche le résultat (confirmé par le webhook).
 *   3. receipt() : la preuve de paiement à montrer au camionneur.
 */
class PaymentController extends Controller
{
    public function pay(Request $request, Due $due): RedirectResponse
    {
        abort_unless($due->user_id === $request->user()->id, 403);

        if ($due->isPaid()) {
            return redirect()->route('dues.receipt', $due);
        }

        // 1. On note la tentative chez nous AVANT d'appeler la passerelle.
        $payment = $due->payments()->create(['user_id' => $request->user()->id, 'amount' => $due->amount]);

        // 2. On demande à SimuPay de créer le paiement (appel d'API, serveur à serveur).
        try {
            $response = $this->simupay()->post('/api/payments', [
                'amount' => $payment->amount,
                'currency' => config('cleancomores.currency'),
                'reference' => $payment->reference(),
                'description' => 'Redevance de collecte · '.$due->passage->village->name.' · '.$due->passage->passed_on->translatedFormat('j F'),
                'callback_url' => route('payments.webhook'),
                'return_url' => route('payments.show', $payment),
            ]);
        } catch (ConnectionException) {
            $response = null;
        }

        if (! $response?->created()) {
            $payment->update(['status' => PaymentStatus::Failed, 'failure_reason' => 'gateway_unavailable']);

            return back()->withErrors(['payment' => 'Le service de paiement ne répond pas. Réessayez dans un instant.']);
        }

        // 3. On garde l'identifiant SimuPay, puis on envoie l'habitant sur la page de paiement.
        $payment->update(['provider_id' => $response->json('id')]);

        return redirect()->away($response->json('checkout_url'));
    }

    public function show(Request $request, Payment $payment): View
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        // Filet de sécurité (« réconciliation ») : si le webhook n'est pas encore arrivé,
        // on demande directement à SimuPay où en est le paiement.
        if ($payment->status === PaymentStatus::Pending && $payment->provider_id) {
            try {
                $remote = $this->simupay()->get('/api/payments/'.$payment->provider_id);
                if ($remote->ok()) {
                    $payment->applyGatewayStatus($remote->json('status'));
                }
            } catch (ConnectionException) {
                // Passerelle injoignable : on réessaiera au prochain affichage.
            }
        }

        return view('payments.show', ['payment' => $payment->fresh(['due.passage.village'])]);
    }

    public function receipt(Request $request, Due $due): View
    {
        abort_unless($due->user_id === $request->user()->id && $due->isPaid(), 404);

        return view('payments.receipt', ['due' => $due->load('passage.village', 'passage.truck', 'user')]);
    }

    /**
     * Client HTTP préconfiguré pour SimuPay : adresse de base, clé secrète, réponses JSON, délai max.
     */
    private function simupay(): PendingRequest
    {
        return Http::baseUrl((string) config('services.simupay.url'))
            ->withToken((string) config('services.simupay.secret_key'))
            ->acceptJson()
            ->timeout(10);
    }
}
