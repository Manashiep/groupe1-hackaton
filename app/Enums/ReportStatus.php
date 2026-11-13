<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReportStatus: string implements HasColor, HasLabel
{
    case Nouveau = 'nouveau';
    case PrisEnCharge = 'pris_en_charge';
    case EnCours = 'en_cours';
    case Nettoye = 'nettoye';
    case Rejete = 'rejete';

    public function getLabel(): string
    {
        return match ($this) {
            self::Nouveau => 'Nouveau',
            self::PrisEnCharge => 'Pris en charge',
            self::EnCours => 'Nettoyage en cours',
            self::Nettoye => 'Nettoyé',
            self::Rejete => 'Rejeté',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Nouveau => 'danger',
            self::PrisEnCharge => 'warning',
            self::EnCours => 'info',
            self::Nettoye => 'success',
            self::Rejete => 'gray',
        };
    }

    /** Couleur des marqueurs sur la carte. */
    public function hex(): string
    {
        return match ($this) {
            self::Nouveau => '#dc2626',
            self::PrisEnCharge => '#d97706',
            self::EnCours => '#2563eb',
            self::Nettoye => '#16a34a',
            self::Rejete => '#6b7280',
        };
    }

    /**
     * Statuts visibles sur la carte des habitants.
     *
     * @return list<self>
     */
    public static function visibleOnMap(): array
    {
        return [self::Nouveau, self::PrisEnCharge, self::EnCours];
    }
}
