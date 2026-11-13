<?php

namespace App\Filament\Resources\Trucks\Tables;

use App\Filament\Resources\Trucks\TruckResource;
use App\Models\Truck;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TrucksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['schedules', 'agents'])->withCount('stops'))
            ->defaultSort('name')
            ->columns([
                ColorColumn::make('color')->label(''),
                TextColumn::make('name')->label('Camion')->description(fn (Truck $record): ?string => $record->plate)->searchable(),
                TextColumn::make('village.name')->label('Village')->placeholder('—'),
                TextColumn::make('days')
                    ->label('Passages')
                    ->state(fn (Truck $record): string => $record->schedules->map(fn ($s) => mb_substr($s->dayLabel(), 0, 3))->implode(', ') ?: '—'),
                TextColumn::make('agents.name')->label('Agents')->badge()->placeholder('—'),
                TextColumn::make('stops_count')->label('Points de collecte'),
            ])
            ->recordActions([
                Action::make('route')
                    ->label('Itinéraire')
                    ->icon(Heroicon::OutlinedMap)
                    ->url(fn (Truck $record): string => TruckResource::getUrl('route', ['record' => $record])),
                EditAction::make(),
            ]);
    }
}
