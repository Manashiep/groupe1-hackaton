<?php

namespace App\Filament\Resources\Villages\Pages;

use App\Filament\Resources\Villages\VillageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVillages extends ManageRecords
{
    protected static string $resource = VillageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
