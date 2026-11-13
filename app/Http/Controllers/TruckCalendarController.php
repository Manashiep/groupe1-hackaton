<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintReason;
use App\Models\TruckSchedule;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Calendrier de ramassage du village choisi : camions, jours, heures et alertes du jour.
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

        $trucks = $village
            ? $village->trucks()
                ->with([
                    'schedules' => fn ($q) => $q->where('village_id', $village->id),
                    'notices' => fn ($q) => $q->recent(),
                ])
                ->orderBy('name')
                ->get()
            : collect();

        return view('trucks.index', [
            'villages' => $villages,
            'village' => $village,
            'trucks' => $trucks,
            'next' => $trucks->flatMap->schedules
                ->sortBy(fn (TruckSchedule $s): string => $s->nextOccurrence()->toDateString().$s->start_time)
                ->first(),
            'reasons' => ComplaintReason::cases(),
        ]);
    }
}
