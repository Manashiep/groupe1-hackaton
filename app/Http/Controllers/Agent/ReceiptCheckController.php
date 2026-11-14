<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Due;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Le camionneur vérifie la preuve de paiement que l'habitant lui montre (code « CC-XXXXXX »).
 */
class ReceiptCheckController extends Controller
{
    public function __invoke(Request $request): View
    {
        $code = Str::upper(trim((string) $request->query('code')));

        return view('agent.receipt-check', [
            'code' => $code,
            'due' => $code !== ''
                ? Due::with('user', 'passage.village')->where('receipt_code', $code)->first()
                : null,
        ]);
    }
}
