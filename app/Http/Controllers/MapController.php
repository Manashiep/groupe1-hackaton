<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\View\View;

/**
 * Écran d'accueil : la carte des dépôts sauvages et le bouton « Signaler ».
 */
class MapController extends Controller
{
    public function index(): View
    {
        $points = Report::visibleOnMap()
            ->with('truck:id,name')
            ->latest()
            ->get()
            ->map(fn (Report $report): array => [
                'lat' => $report->latitude,
                'lng' => $report->longitude,
                'status' => $report->status->getLabel(),
                'color' => $report->status->hex(),
                'description' => $report->description,
                'photo' => $report->photoUrl(),
                'truck' => $report->truck?->name,
                'cleanup' => $report->cleanupLabel(),
                'date' => $report->created_at->diffForHumans(),
            ]);

        return view('map', ['points' => $points]);
    }
}
