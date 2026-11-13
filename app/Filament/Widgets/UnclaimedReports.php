<?php

namespace App\Filament\Widgets;

use App\Enums\ReportStatus;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dépôts que personne n'a encore pris en charge, les plus anciens d'abord.
 */
class UnclaimedReports extends TableWidget
{
    protected static ?string $heading = 'Dépôts sans volontaire';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Report::query()->with('village')->where('status', ReportStatus::Nouveau)->oldest())
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->emptyStateHeading('Tous les dépôts ont un volontaire')
            ->emptyStateIcon(Heroicon::OutlinedFaceSmile)
            ->columns([
                TextColumn::make('description')
                    ->label('Dépôt')
                    ->limit(34)
                    ->placeholder('Dépôt sauvage')
                    ->description(fn (Report $record): ?string => $record->village?->name),
                TextColumn::make('created_at')->label('Depuis')->since(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Voir')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(ReportResource::getUrl('index')),
            ]);
    }
}
