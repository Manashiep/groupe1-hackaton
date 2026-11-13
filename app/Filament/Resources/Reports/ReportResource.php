<?php

namespace App\Filament\Resources\Reports;

use App\Enums\ReportStatus;
use App\Filament\Resources\Reports\Pages\ManageReports;
use App\Models\Report;
use App\Models\Truck;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Signalements des habitants : prise en charge par un camion (jour + heure) ou rejet.
 */
class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $slug = 'signalements';

    protected static ?string $navigationLabel = 'Signalements';

    protected static ?string $modelLabel = 'signalement';

    protected static ?string $pluralModelLabel = 'signalements';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = Report::where('status', ReportStatus::Nouveau)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('photo_path')->label('Photo')->disk('public')->imageHeight(220)->placeholder('Pas de photo')->columnSpanFull(),
                TextEntry::make('description')->placeholder('—')->columnSpanFull(),
                TextEntry::make('status')->label('Statut')->badge(),
                TextEntry::make('village.name')->label('Village')->placeholder('—'),
                TextEntry::make('position')
                    ->label('Position')
                    ->state('Voir sur la carte')
                    ->url(fn (Report $record): string => "https://www.openstreetmap.org/?mlat={$record->latitude}&mlon={$record->longitude}#map=18/{$record->latitude}/{$record->longitude}", shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedMap),
                TextEntry::make('user.name')->label('Signalé par')->placeholder('—'),
                TextEntry::make('truck.name')->label('Camion')->placeholder('—'),
                TextEntry::make('cleanup_at')->label('Nettoyage prévu')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i'),
                TextEntry::make('admin_note')->label('Motif du rejet')->placeholder('—')->visible(fn (Report $record): bool => $record->status === ReportStatus::Rejete),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('photo_path')->label('Photo')->disk('public')->imageSize(48)->square(),
                TextColumn::make('description')->limit(40)->placeholder('—')->searchable(),
                TextColumn::make('village.name')->label('Village')->placeholder('—'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('cleanup_at')
                    ->label('Nettoyage prévu')
                    ->dateTime('d/m H:i')
                    ->description(fn (Report $record): ?string => $record->truck?->name)
                    ->placeholder('—'),
                TextColumn::make('created_at')->label('Reçu')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(ReportStatus::class),
                SelectFilter::make('village')->label('Village')->relationship('village', 'name'),
            ])
            ->recordActions([
                ViewAction::make()->label('Voir'),
                Action::make('assign')
                    ->label(fn (Report $record): string => $record->status === ReportStatus::Nouveau ? 'Prendre en charge' : 'Replanifier')
                    ->icon(Heroicon::OutlinedTruck)
                    ->color('primary')
                    ->visible(fn (Report $record): bool => in_array($record->status, [ReportStatus::Nouveau, ReportStatus::PrisEnCharge], true))
                    ->fillForm(fn (Report $record): array => [
                        'truck_id' => $record->truck_id ?? Truck::where('village_id', $record->village_id)->value('id'),
                        'cleanup_at' => $record->cleanup_at ?? today()->addDay()->setTime(9, 0),
                    ])
                    ->schema([
                        Select::make('truck_id')->label('Camion')->options(Truck::pluck('name', 'id'))->required(),
                        DateTimePicker::make('cleanup_at')->label('Jour et heure du nettoyage')->seconds(false)->required(),
                    ])
                    ->action(fn (Report $record, array $data) => $record->update([...$data, 'status' => ReportStatus::PrisEnCharge]))
                    ->successNotificationTitle('Signalement pris en charge'),
                Action::make('reject')
                    ->label('Rejeter')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('gray')
                    ->visible(fn (Report $record): bool => $record->status === ReportStatus::Nouveau)
                    ->schema([
                        Textarea::make('admin_note')->label('Motif (visible par l\'habitant)')->required()->maxLength(500),
                    ])
                    ->action(fn (Report $record, array $data) => $record->update([...$data, 'status' => ReportStatus::Rejete]))
                    ->successNotificationTitle('Signalement rejeté'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageReports::route('/'),
        ];
    }
}
