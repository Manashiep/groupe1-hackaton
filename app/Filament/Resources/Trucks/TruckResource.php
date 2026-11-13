<?php

namespace App\Filament\Resources\Trucks;

use App\Filament\Resources\Trucks\Pages\CreateTruck;
use App\Filament\Resources\Trucks\Pages\EditTruck;
use App\Filament\Resources\Trucks\Pages\EditTruckRoute;
use App\Filament\Resources\Trucks\Pages\ListTrucks;
use App\Filament\Resources\Trucks\Schemas\TruckForm;
use App\Filament\Resources\Trucks\Tables\TrucksTable;
use App\Models\Truck;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Camions : village affecté, jours de passage et itinéraire de la tournée.
 */
class TruckResource extends Resource
{
    protected static ?string $model = Truck::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'camions';

    protected static ?string $navigationLabel = 'Camions';

    protected static ?string $modelLabel = 'camion';

    protected static ?string $pluralModelLabel = 'camions';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return TruckForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrucksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrucks::route('/'),
            'create' => CreateTruck::route('/create'),
            'edit' => EditTruck::route('/{record}/edit'),
            'route' => EditTruckRoute::route('/{record}/itineraire'),
        ];
    }
}
