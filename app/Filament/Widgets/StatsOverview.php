<?php

namespace App\Filament\Widgets;

use App\Enums\AssociationStatus;
use App\Enums\ReportStatus;
use App\Filament\Resources\Associations\AssociationResource;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Association;
use App\Models\Report;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Chiffres clés de la semaine, avec leur tendance sur 7 jours.
 */
class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 4;
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $unclaimed = Report::where('status', ReportStatus::Nouveau)->count();
        $plannedToday = Report::whereIn('status', [ReportStatus::PrisEnCharge, ReportStatus::EnCours])->whereDate('cleanup_at', today())->count();
        $pendingAssociations = Association::where('status', AssociationStatus::EnAttente)->count();

        return [
            Stat::make('Signalements (7 jours)', Report::where('created_at', '>=', today()->subDays(6))->count())
                ->description($unclaimed.' sans volontaire')
                ->descriptionIcon(Heroicon::ExclamationCircle)
                ->color($unclaimed > 0 ? 'danger' : 'success')
                ->chart($this->dailyCounts('created_at'))
                ->url(ReportResource::getUrl('index')),
            Stat::make('Zones nettoyées (7 jours)', Report::where('status', ReportStatus::Nettoye)->where('cleaned_at', '>=', today()->subDays(6))->count())
                ->description('Par les citoyens et associations')
                ->descriptionIcon(Heroicon::Sparkles)
                ->color('success')
                ->chart($this->dailyCounts('cleaned_at', ReportStatus::Nettoye)),
            Stat::make('Nettoyages prévus', Report::whereIn('status', [ReportStatus::PrisEnCharge, ReportStatus::EnCours])->count())
                ->description($plannedToday.' aujourd\'hui')
                ->descriptionIcon(Heroicon::CalendarDays)
                ->color('warning'),
            Stat::make('Associations actives', Association::where('status', AssociationStatus::Active)->count())
                ->description($pendingAssociations ? $pendingAssociations.' demande(s) à valider' : 'Aucune demande en attente')
                ->descriptionIcon(Heroicon::UserGroup)
                ->color($pendingAssociations ? 'warning' : 'gray')
                ->url(AssociationResource::getUrl('index')),
        ];
    }

    /**
     * Nombre de signalements par jour sur les 7 derniers jours (pour la courbe de tendance).
     *
     * @return list<int>
     */
    private function dailyCounts(string $column, ?ReportStatus $status = null): array
    {
        $counts = Report::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->where($column, '>=', today()->subDays(6))
            ->get([$column])
            ->countBy(fn (Report $report): string => Carbon::parse($report->{$column})->toDateString());

        return collect(range(6, 0))
            ->map(fn (int $daysAgo): int => $counts[today()->subDays($daysAgo)->toDateString()] ?? 0)
            ->all();
    }
}
