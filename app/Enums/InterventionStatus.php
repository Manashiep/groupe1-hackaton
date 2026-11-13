<?php

namespace App\Enums;

enum InterventionStatus: string
{
    case Planifiee = 'planifiee';
    case EnCours = 'en_cours';
    case Terminee = 'terminee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Planifiee => 'Planifiée',
            self::EnCours => 'En cours',
            self::Terminee => 'Terminée',
            self::Annulee => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planifiee => 'info',
            self::EnCours => 'primary',
            self::Terminee => 'success',
            self::Annulee => 'secondary',
        };
    }
}
