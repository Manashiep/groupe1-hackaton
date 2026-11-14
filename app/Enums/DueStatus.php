<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DueStatus: string implements HasColor, HasLabel
{
    case APayer = 'a_payer';
    case Payee = 'payee';

    public function getLabel(): string
    {
        return match ($this) {
            self::APayer => 'À payer',
            self::Payee => 'Payée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::APayer => 'danger',
            self::Payee => 'success',
        };
    }
}
