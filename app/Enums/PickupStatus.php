<?php

namespace App\Enums;

enum PickupStatus: string
{
    case EnAttente = 'en_attente';
    case Acceptee = 'acceptee';
    case Effectuee = 'effectuee';
    case Refusee = 'refusee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Acceptee => 'Rendez-vous fixé',
            self::Effectuee => 'Collecte effectuée',
            self::Refusee => 'Refusée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Acceptee => 'info',
            self::Effectuee => 'success',
            self::Refusee => 'secondary',
        };
    }
}
