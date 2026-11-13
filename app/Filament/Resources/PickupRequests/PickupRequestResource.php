<?php

namespace App\Filament\Resources\PickupRequests;

use App\Enums\PickupStatus;
use App\Filament\Resources\PickupRequests\Pages\ManagePickupRequests;
use App\Models\PickupRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Demandes de collecte : prises en charge par des volontaires, la mairie peut en refuser.
 */
class PickupRequestResource extends Resource
{
    protected static ?string $model = PickupRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $slug = 'demandes';

    protected static ?string $navigationLabel = 'Demandes de collecte';

    protected static ?string $modelLabel = 'demande de collecte';

    protected static ?string $pluralModelLabel = 'demandes de collecte';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'village', 'taker', 'association']))
            ->columns([
                TextColumn::make('description')->limit(50)->wrap()->searchable(),
                TextColumn::make('user.name')->label('Demandeur')->description(fn (PickupRequest $record): ?string => $record->user?->phone),
                TextColumn::make('village.name')->label('Village')->placeholder('—'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('appointment_at')
                    ->label('Pris en charge')
                    ->dateTime('d/m H:i')
                    ->description(fn (PickupRequest $record): ?string => $record->takerName())
                    ->placeholder('—'),
                TextColumn::make('created_at')->label('Reçue')->since(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(PickupStatus::class),
            ])
            ->recordActions([
                Action::make('map')
                    ->label('Carte')
                    ->icon(Heroicon::OutlinedMap)
                    ->color('gray')
                    ->url(fn (PickupRequest $record): string => "https://www.openstreetmap.org/?mlat={$record->latitude}&mlon={$record->longitude}#map=18/{$record->latitude}/{$record->longitude}", shouldOpenInNewTab: true),
                Action::make('refuse')
                    ->label('Refuser')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('gray')
                    ->visible(fn (PickupRequest $record): bool => $record->status === PickupStatus::EnAttente)
                    ->schema([
                        Textarea::make('admin_note')->label('Motif (visible par l\'habitant)')->required()->maxLength(500),
                    ])
                    ->action(fn (PickupRequest $record, array $data) => $record->update([...$data, 'status' => PickupStatus::Refusee]))
                    ->successNotificationTitle('Demande refusée'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePickupRequests::route('/'),
        ];
    }
}
