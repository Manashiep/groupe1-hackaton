<?php

namespace App\Enums;

/**
 * Alerte envoyée par l'agent aux habitants des villages de son camion.
 */
enum NoticeKind: string
{
    case NonTerminee = 'non_terminee';
    case Retard = 'retard';
    case Annule = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::NonTerminee => 'Tournée pas terminée',
            self::Retard => 'Le camion a du retard',
            self::Annule => 'Passage annulé aujourd\'hui',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::NonTerminee => 'bi-hourglass-split',
            self::Retard => 'bi-clock-history',
            self::Annule => 'bi-x-octagon',
        };
    }
}
