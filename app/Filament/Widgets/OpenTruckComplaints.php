<?php

namespace App\Filament\Widgets;

use App\Models\TruckComplaint;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Problèmes de camion signalés par les habitants et pas encore traités.
 */
class OpenTruckComplaints extends TableWidget
{
    protected static ?string $heading = 'Problèmes de camion à traiter';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => TruckComplaint::query()->with('truck')->whereNull('handled_at')->latest())
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->emptyStateHeading('Aucun problème en attente')
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->columns([
                TextColumn::make('truck.name')
                    ->label('Camion')
                    ->description(fn (TruckComplaint $record): string => $record->created_at->diffForHumans()),
                TextColumn::make('message')
                    ->label('Problème')
                    ->limit(40)
                    ->state(fn (TruckComplaint $record): string => $record->message ?: $record->reason->getLabel())
                    ->description(fn (TruckComplaint $record): string => $record->reason->getLabel()),
            ])
            ->recordActions([
                Action::make('handle')
                    ->label('Traité')
                    ->icon(Heroicon::OutlinedCheck)
                    ->action(fn (TruckComplaint $record) => $record->update(['handled_at' => now()])),
            ]);
    }
}
