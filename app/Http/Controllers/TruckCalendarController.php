<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintReason;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Calendrier de passage du camion du village choisi par l'habitant.
 */
class TruckCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $villages = Village::orderBy('name')->get();
        $user = $request->user();

        $village = $villages->firstWhere('slug', $request->query('village'))
            ?? $villages->firstWhere('id', $user->village_id);

        // On retient le dernier village consulté pour l'afficher directement la prochaine fois.
        if ($village && $user->village_id !== $village->id) {
            $user->update(['village_id' => $village->id]);
        }

        return view('trucks.index', [
            'villages' => $villages,
            'village' => $village,
            'trucks' => $village?->trucks()->with('schedules')->get() ?? collect(),
            'reasons' => ComplaintReason::cases(),
        ]);
    }
}
