<?php

namespace App\Filament\Resources\Villages;

use App\Filament\Resources\Villages\Pages\ManageVillages;
use App\Models\Village;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VillageResource extends Resource
{
    protected static ?string $model = Village::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $slug = 'villages';

    protected static ?string $navigationLabel = 'Villages';

    protected static ?string $modelLabel = 'village';

    protected static ?string $pluralModelLabel = 'villages';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nom')->required()->maxLength(100)->columnSpanFull(),
                TextInput::make('latitude')->required()->numeric()->minValue(-12.6)->maxValue(-11.2),
                TextInput::make('longitude')->required()->numeric()->minValue(43.1)->maxValue(44.7),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Village')->searchable(),
                TextColumn::make('trucks.name')->label('Camion')->badge()->placeholder('Aucun camion'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVillages::route('/'),
        ];
    }
}
