<?php

namespace App\Filament\Resources\Trucks\Schemas;

use App\Models\TruckSchedule;
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
                        Select::make('village_id')->label('Village affecté')->relationship('village', 'name')->required(),
                        ColorPicker::make('color')->label('Couleur sur la carte')->default('#0f766e')->required(),
                    ]),
                Section::make('Jours de passage')
                    ->description('Ce calendrier est visible par les habitants du village.')
                    ->schema([
                        Repeater::make('schedules')
                            ->hiddenLabel()
                            ->relationship()
                            ->columns(3)
                            ->addActionLabel('Ajouter un jour')
                            ->defaultItems(0)
                            ->schema([
                                Select::make('day_of_week')->label('Jour')->options(TruckSchedule::DAYS)->required(),
                                TimePicker::make('start_time')->label('Début')->seconds(false)->required(),
                                TimePicker::make('end_time')->label('Fin')->seconds(false)->after('start_time'),
                            ]),
                    ]),
            ]);
    }
}
