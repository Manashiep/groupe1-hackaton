<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\TruckPassage;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * L'agent confirme « passage terminé » dans un village : les habitants de ce village reçoivent leur redevance.
 */
class TruckPassageController extends Controller
{
    public function store(Request $request, Village $village): RedirectResponse
    {
        $truck = $request->user()->truck;

        // L'agent ne peut confirmer qu'un village desservi par son propre camion.
        abort_unless($truck && $truck->villages()->whereKey($village->id)->exists(), 403);

        $passage = TruckPassage::record($truck, $village, $request->user());

        return back()->with('success', "Passage enregistré à {$village->name} : {$passage->dues()->count()} habitant(s) facturé(s).");
    }
}
