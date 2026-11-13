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
            self::Nouveau => 'À nettoyer',
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

    /** Couleur de la pastille (carte, listes). */
    public function hex(): string
    {
        return match ($this) {
            self::Nouveau => '#d64a32',
            self::PrisEnCharge => '#d9941c',
            self::EnCours => '#3567c4',
            self::Nettoye => '#2e8b57',
            self::Rejete => '#7c8781',
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
