<?php

namespace App\Filament\Resources\Trucks\Schemas;

use App\Models\TruckSchedule;
use App\Models\Village;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TruckForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Camion')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Nom')->required()->maxLength(100),
                        TextInput::make('plate')->label('Immatriculation')->maxLength(30),
                        Select::make('villages')
                            ->label('Villages desservis')
                            ->relationship('villages', 'name')
                            ->multiple()
                            ->preload()
                            ->required(),
                        ColorPicker::make('color')->label('Couleur sur la carte')->default('#1d6b47')->required()
                            // La couleur est injectée dans les cartes : seul un code #RRGGBB est accepté, jamais du texte libre.
                            ->regex('/^#[0-9a-fA-F]{6}$/')
                            ->validationMessages(['regex' => 'Choisissez une couleur au format #RRGGBB.']),
                    ]),
                Section::make('Calendrier de ramassage')
                    ->description('Un créneau par village et par jour. Les habitants le voient dans l\'onglet « Camion ».')
                    ->schema([
                        Repeater::make('schedules')
                            ->hiddenLabel()
                            ->relationship()
                            ->columns(4)
                            ->addActionLabel('Ajouter un créneau')
                            ->defaultItems(0)
                            ->schema([
                                Select::make('village_id')->label('Village')->options(fn () => Village::orderBy('name')->pluck('name', 'id'))->required(),
                                Select::make('day_of_week')->label('Jour')->options(TruckSchedule::DAYS)->required(),
                                TimePicker::make('start_time')->label('Début')->seconds(false)->required(),
                                TimePicker::make('end_time')->label('Fin')->seconds(false)->after('start_time'),
                            ]),
                    ]),
            ]);
    }
}
