<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PickupStatus: string implements HasColor, HasLabel
{
    case EnAttente = 'en_attente';
    case PriseEnCharge = 'prise_en_charge';
    case Effectuee = 'effectuee';
    case Refusee = 'refusee';

    public function getLabel(): string
    {
        return match ($this) {
            self::EnAttente => 'Cherche un volontaire',
            self::PriseEnCharge => 'Prise en charge',
            self::Effectuee => 'Collecte faite',
            self::Refusee => 'Refusée par la mairie',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EnAttente => 'danger',
            self::PriseEnCharge => 'warning',
            self::Effectuee => 'success',
            self::Refusee => 'gray',
        };
    }

    public function hex(): string
    {
        return match ($this) {
            self::EnAttente => '#d64a32',
            self::PriseEnCharge => '#d9941c',
            self::Effectuee => '#2e8b57',
            self::Refusee => '#7c8781',
        };
    }
}
