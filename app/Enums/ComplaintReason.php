<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ComplaintReason: string implements HasColor, HasLabel
{
    case Retard = 'retard';
    case Comportement = 'comportement';
    case Autre = 'autre';

    public function getLabel(): string
    {
        return match ($this) {
            self::Retard => 'Le camion est en retard ou n\'est pas passé',
            self::Comportement => 'Mauvais comportement de l\'équipe',
            self::Autre => 'Autre problème',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Retard => 'warning',
            self::Comportement => 'danger',
            self::Autre => 'gray',
        };
    }
}
