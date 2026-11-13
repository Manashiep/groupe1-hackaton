<?php

namespace App\Filament\Resources\PickupRequests;

use App\Enums\PickupStatus;
use App\Filament\Resources\PickupRequests\Pages\ManagePickupRequests;
use App\Models\PickupRequest;
use App\Models\Truck;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Demandes de collecte : rendez-vous avec un camion ou refus motivé.
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

    public static function getNavigationBadge(): ?string
    {
        $count = PickupRequest::where('status', PickupStatus::EnAttente)->count();

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
                TextColumn::make('description')->limit(50)->wrap()->searchable(),
                TextColumn::make('user.name')->label('Demandeur')->description(fn (PickupRequest $record): ?string => $record->user?->phone),
                TextColumn::make('village.name')->label('Village')->placeholder('—'),
                TextColumn::make('preferred_date')->label('Date souhaitée')->date('d/m')->placeholder('—'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('appointment_at')->label('Rendez-vous')->dateTime('d/m H:i')->description(fn (PickupRequest $record): ?string => $record->truck?->name)->placeholder('—'),
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
                Action::make('accept')
                    ->label('Accepter')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->visible(fn (PickupRequest $record): bool => $record->status === PickupStatus::EnAttente)
                    ->fillForm(fn (PickupRequest $record): array => [
                        'truck_id' => Truck::where('village_id', $record->village_id)->value('id'),
                        'appointment_at' => ($record->preferred_date ?? today()->addDay())->copy()->setTime(10, 0),
                    ])
                    ->schema([
                        Select::make('truck_id')->label('Camion')->options(Truck::pluck('name', 'id'))->required(),
                        DateTimePicker::make('appointment_at')->label('Jour et heure du passage')->seconds(false)->required(),
                        Textarea::make('admin_note')->label('Message pour l\'habitant (facultatif)')->maxLength(500),
                    ])
                    ->action(fn (PickupRequest $record, array $data) => $record->update([...$data, 'status' => PickupStatus::Acceptee]))
                    ->successNotificationTitle('Rendez-vous fixé'),
                Action::make('refuse')
                    ->label('Refuser')
                    ->icon(Heroicon::OutlinedXMark)
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
