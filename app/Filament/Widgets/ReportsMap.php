<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PickupRequests\PickupRequestResource;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\PickupRequest;
use App\Models\Report;
use Filament\Widgets\Widget;

/**
 * La même carte que celle des habitants, dans le tableau de bord de la mairie.
 * Chaque point ouvre la fiche correspondante dans l'espace mairie (pas dans l'appli des habitants).
 */
class ReportsMap extends Widget
{
    protected string $view = 'filament.widgets.reports-map';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    /** Rendue avec la page : Leaflet doit être chargé avant que la carte ne se dessine. */
    protected static bool $isLazy = false;

    /**
     * @return list<array<string, mixed>>
     */
    public function getPoints(): array
    {
        $reports = Report::visibleOnMap()->with(['taker', 'association'])->latest()->get()
            ->map(fn (Report $report): array => $report->toMapPoint(
                ReportResource::getUrl('index', ['tableAction' => 'view', 'tableActionRecord' => $report->getKey()]),
            ));

        $pickups = PickupRequest::visibleOnMap()->with(['taker', 'association'])->latest()->get()
            ->map(fn (PickupRequest $pickup): array => $pickup->toMapPoint(PickupRequestResource::getUrl('index')));

        return $reports->concat($pickups)->values()->all();
    }
}
