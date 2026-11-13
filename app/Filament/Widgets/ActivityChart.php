<?php

namespace App\Filament\Widgets;

use App\Enums\ReportStatus;
use App\Models\Report;
use Filament\Widgets\ChartWidget;

/**
 * Signalements reçus et zones nettoyées, semaine par semaine.
 */
class ActivityChart extends ChartWidget
{
    protected ?string $heading = 'Signalements et nettoyages';

    protected ?string $maxHeight = '280px';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getDescription(): ?string
    {
        return 'Par semaine, sur les 8 dernières semaines.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $weeks = collect(range(7, 0))->map(fn (int $weeksAgo) => now()->startOfWeek()->subWeeks($weeksAgo));

        $count = fn (string $column, ?ReportStatus $status) => $weeks->map(fn ($start): int => Report::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->whereBetween($column, [$start, $start->copy()->endOfWeek()])
            ->count())->all();

        // Couleurs catégorielles validées (contraste et daltonisme) : bleu pour reçus, vert d'eau pour nettoyés.
        return [
            'datasets' => [
                [
                    'label' => 'Signalés',
                    'data' => $count('created_at', null),
                    'borderColor' => '#2a78d6',
                    'backgroundColor' => '#2a78d6',
                    'borderWidth' => 2,
                    'pointRadius' => 4,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Nettoyés',
                    'data' => $count('cleaned_at', ReportStatus::Nettoye),
                    'borderColor' => '#1baf7a',
                    'backgroundColor' => '#1baf7a',
                    'borderWidth' => 2,
                    'pointRadius' => 4,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $weeks->map(fn ($start): string => $start->translatedFormat('j M'))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                'x' => ['grid' => ['display' => false]],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
