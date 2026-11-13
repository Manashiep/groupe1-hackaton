<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AssociationStatus: string implements HasColor, HasLabel
{
    case EnAttente = 'en_attente';
    case Active = 'active';
    case Refusee = 'refusee';

    public function getLabel(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente de la mairie',
            self::Active => 'Active',
            self::Refusee => 'Refusée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Active => 'success',
            self::Refusee => 'gray',
        };
    }
}
