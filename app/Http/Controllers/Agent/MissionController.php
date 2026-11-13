<?php

namespace App\Http\Controllers\Agent;

use App\Enums\PickupStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Missions confiées au camion de l'agent : nettoyage de dépôts et collectes sur demande.
 */
class MissionController extends Controller
{
    public function startReport(Request $request, Report $report): RedirectResponse
    {
        $this->ensureOwnTruck($request, $report->truck_id);

        $report->update(['status' => ReportStatus::EnCours]);

        return back();
    }

    public function finishReport(Request $request, Report $report): RedirectResponse
    {
        $this->ensureOwnTruck($request, $report->truck_id);

        $report->update(['status' => ReportStatus::Nettoye, 'cleaned_at' => now()]);

        return back()->with('success', 'Zone marquée comme nettoyée.');
    }

    public function finishPickup(Request $request, PickupRequest $pickup): RedirectResponse
    {
        $this->ensureOwnTruck($request, $pickup->truck_id);

        $pickup->update(['status' => PickupStatus::Effectuee, 'done_at' => now()]);

        return back()->with('success', 'Collecte marquée comme effectuée.');
    }

    private function ensureOwnTruck(Request $request, ?int $truckId): void
    {
        abort_unless($truckId !== null && $truckId === $request->user()->truck_id, 403);
    }
}
