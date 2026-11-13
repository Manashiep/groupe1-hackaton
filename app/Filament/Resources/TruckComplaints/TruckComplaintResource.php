<?php

namespace App\Filament\Resources\TruckComplaints;

use App\Enums\ComplaintReason;
use App\Filament\Resources\TruckComplaints\Pages\ManageTruckComplaints;
use App\Models\TruckComplaint;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Problèmes de camion signalés par les habitants (retard, comportement…).
 */
class TruckComplaintResource extends Resource
{
    protected static ?string $model = TruckComplaint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $slug = 'problemes-camion';

    protected static ?string $navigationLabel = 'Problèmes de camion';

    protected static ?string $modelLabel = 'problème de camion';

    protected static ?string $pluralModelLabel = 'problèmes de camion';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $count = TruckComplaint::whereNull('handled_at')->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('truck.name')->label('Camion')->description(fn (TruckComplaint $record): ?string => $record->truck?->villages->pluck('name')->join(', ')),
                TextColumn::make('reason')->label('Problème')->badge(),
                TextColumn::make('message')->wrap()->placeholder('—'),
                TextColumn::make('user.name')->label('Habitant')->description(fn (TruckComplaint $record): ?string => $record->user?->phone),
                TextColumn::make('created_at')->label('Reçu')->since(),
                IconColumn::make('handled_at')->label('Traité')->boolean()->getStateUsing(fn (TruckComplaint $record): bool => $record->handled_at !== null),
            ])
            ->filters([
                TernaryFilter::make('handled_at')->label('Traité')->nullable()->default(false),
                SelectFilter::make('reason')->label('Problème')->options(ComplaintReason::class),
            ])
            ->recordActions([
                Action::make('handle')
                    ->label('Marquer traité')
                    ->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (TruckComplaint $record): bool => $record->handled_at === null)
                    ->action(fn (TruckComplaint $record) => $record->update(['handled_at' => now()])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTruckComplaints::route('/'),
        ];
    }
}
