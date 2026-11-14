<?php

namespace App\Http\Controllers;

use App\Models\PickupRequest;
use App\Models\Report;
use Illuminate\View\View;

/**
 * Écran d'accueil : la carte des dépôts à nettoyer et des demandes de collecte.
 */
class MapController extends Controller
{
    public function index(): View
    {
        $reports = Report::visibleOnMap()->with(['taker', 'association'])->latest()->get()
            ->map(fn (Report $report): array => $report->toMapPoint(route('reports.show', $report)));

        $pickups = PickupRequest::visibleOnMap()->with(['taker', 'association'])->latest()->get()
            ->map(fn (PickupRequest $pickup): array => $pickup->toMapPoint(route('pickups.show', $pickup)));

        return view('map', ['points' => $reports->concat($pickups)->values()]);
    }
}
