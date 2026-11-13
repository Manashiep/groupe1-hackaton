<?php

namespace App\Http\Controllers;

use App\Enums\PickupStatus;
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
        $reports = Report::visibleOnMap()
            ->with(['taker', 'association'])
            ->latest()
            ->get()
            ->map(fn (Report $report): array => [
                'kind' => 'report',
                'lat' => $report->latitude,
                'lng' => $report->longitude,
                'status' => $report->status->getLabel(),
                'statusClass' => 'st-'.$report->status->value,
                'color' => $report->status->hex(),
                'description' => $report->description,
                'photo' => $report->photoUrl(),
                'taker' => $report->takerName(),
                'planned' => $report->cleanupLabel(),
                'date' => $report->created_at->diffForHumans(),
                'url' => route('reports.show', $report),
            ]);

        $pickups = PickupRequest::whereIn('status', [PickupStatus::EnAttente, PickupStatus::PriseEnCharge])
            ->with(['taker', 'association'])
            ->latest()
            ->get()
            ->map(fn (PickupRequest $pickup): array => [
                'kind' => 'pickup',
                'lat' => $pickup->latitude,
                'lng' => $pickup->longitude,
                'status' => $pickup->status->getLabel(),
                'statusClass' => 'st-'.$pickup->status->value,
                'color' => $pickup->status->hex(),
                'description' => $pickup->description,
                'photo' => null,
                'taker' => $pickup->takerName(),
                'planned' => $pickup->appointmentLabel(),
                'date' => $pickup->created_at->diffForHumans(),
                'url' => route('pickups.show', $pickup),
            ]);

        return view('map', ['points' => $reports->concat($pickups)->values()]);
    }
}
