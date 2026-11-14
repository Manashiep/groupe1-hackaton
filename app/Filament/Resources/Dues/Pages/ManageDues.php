<?php

namespace App\Filament\Resources\Dues\Pages;

use App\Filament\Resources\Dues\DueResource;
use Filament\Resources\Pages\ManageRecords;

class ManageDues extends ManageRecords
{
    protected static string $resource = DueResource::class;
}
