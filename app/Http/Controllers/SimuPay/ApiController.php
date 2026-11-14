<?php

namespace App\Http\Controllers\SimuPay;

use App\Http\Controllers\Controller;
use App\Models\SimuPayTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * API de SimuPay, la fausse passerelle de paiement.
 * C'est ce que EcoCom appelle « de l'extérieur », exactement comme on appellerait un vrai opérateur.
 *
 *   POST /simupay/api/payments        → créer un paiement (renvoie l'adresse de la page de paiement)
 *   GET  /simupay/api/payments/{id}   → connaître l'état d'un paiement
 *
 * Chaque appel doit porter l'en-tête « Authorization: Bearer <clé secrète> ».
 */
class ApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (! $this->hasValidSecretKey($request)) {
            return response()->json(['error' => 'Clé API invalide.'], 401);
        }

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['required', Rule::in(['KMF'])],
            'reference' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'callback_url' => ['required', 'url'],
            'return_url' => ['required', 'url'],
        ]);

        $transaction = SimuPayTransaction::create([
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'merchant_reference' => $data['reference'],
            'description' => $data['description'] ?? null,
            'callback_url' => $data['callback_url'],
            'return_url' => $data['return_url'],
        ]);

        return response()->json($transaction->toApiArray(), 201);
    }

    public function show(Request $request, SimuPayTransaction $transaction): JsonResponse
    {
        if (! $this->hasValidSecretKey($request)) {
            return response()->json(['error' => 'Clé API invalide.'], 401);
        }

        return response()->json($transaction->toApiArray());
    }

    /**
     * Compare la clé reçue avec la clé attendue sans fuite d'information sur le temps de comparaison.
     */
    private function hasValidSecretKey(Request $request): bool
    {
        $expected = (string) config('services.simupay.secret_key');

        return $expected !== '' && hash_equals($expected, (string) $request->bearerToken());
    }
}
