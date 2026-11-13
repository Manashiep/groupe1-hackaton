<?php

namespace App\Http\Controllers\Agent;

use App\Enums\NoticeKind;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * L'agent prévient les habitants de ses villages (tournée pas terminée, retard, annulation).
 */
class TruckNoticeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $truck = $request->user()->truck;
        abort_unless($truck !== null, 403);

        $data = $request->validate([
            'kind' => ['required', Rule::enum(NoticeKind::class)],
            'message' => ['nullable', 'string', 'max:300'],
        ]);

        $truck->notices()->create([...$data, 'user_id' => $request->user()->id]);

        return back()->with('success', 'Les habitants de vos villages sont prévenus.');
    }
}
