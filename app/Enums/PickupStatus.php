<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PickupStatus: string implements HasColor, HasLabel
{
    case EnAttente = 'en_attente';
    case Acceptee = 'acceptee';
    case Effectuee = 'effectuee';
    case Refusee = 'refusee';

    public function getLabel(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Acceptee => 'Rendez-vous fixé',
            self::Effectuee => 'Collecte effectuée',
            self::Refusee => 'Refusée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Acceptee => 'info',
            self::Effectuee => 'success',
            self::Refusee => 'gray',
        };
    }

    public function hex(): string
    {
        return match ($this) {
            self::EnAttente => '#d97706',
            self::Acceptee => '#2563eb',
            self::Effectuee => '#16a34a',
            self::Refusee => '#6b7280',
        };
    }
}
