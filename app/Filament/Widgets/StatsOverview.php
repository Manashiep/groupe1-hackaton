<?php

namespace App\Filament\Widgets;

use App\Enums\PickupStatus;
use App\Enums\ReportStatus;
use App\Filament\Resources\PickupRequests\PickupRequestResource;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\TruckComplaints\TruckComplaintResource;
use App\Models\PickupRequest;
use App\Models\Report;
use App\Models\TruckComplaint;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Ce qui attend la mairie aujourd'hui, en un coup d'œil.
 */
class StatsOverview extends StatsOverviewWidget
{
    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        return [
            Stat::make('Signalements à traiter', Report::where('status', ReportStatus::Nouveau)->count())
                ->color('danger')
                ->url(ReportResource::getUrl('index')),
            Stat::make('Nettoyages planifiés', Report::whereIn('status', [ReportStatus::PrisEnCharge, ReportStatus::EnCours])->count())
                ->color('warning'),
            Stat::make('Zones nettoyées (30 jours)', Report::where('status', ReportStatus::Nettoye)->where('cleaned_at', '>=', now()->subDays(30))->count())
                ->color('success'),
            Stat::make('Demandes de collecte en attente', PickupRequest::where('status', PickupStatus::EnAttente)->count())
                ->url(PickupRequestResource::getUrl('index')),
            Stat::make('Problèmes de camion', TruckComplaint::whereNull('handled_at')->count())
                ->url(TruckComplaintResource::getUrl('index')),
        ];
    }
}
