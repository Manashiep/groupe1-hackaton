<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Nouveau = 'nouveau';
    case Valide = 'valide';
    case Planifie = 'planifie';
    case EnCours = 'en_cours';
    case Resolu = 'resolu';
    case Rejete = 'rejete';

    public function label(): string
    {
        return match ($this) {
            self::Nouveau => 'Nouveau',
            self::Valide => 'Vérifié',
            self::Planifie => 'Intervention planifiée',
            self::EnCours => 'Nettoyage en cours',
            self::Resolu => 'Zone nettoyée',
            self::Rejete => 'Rejeté',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Nouveau => 'danger',
            self::Valide => 'warning',
            self::Planifie => 'info',
            self::EnCours => 'primary',
            self::Resolu => 'success',
            self::Rejete => 'secondary',
        };
    }

    /** Hex colour used by Leaflet markers. */
    public function hex(): string
    {
        return match ($this) {
            self::Nouveau => '#dc2626',
            self::Valide => '#d97706',
            self::Planifie => '#0891b2',
            self::EnCours => '#2563eb',
            self::Resolu => '#16a34a',
            self::Rejete => '#6b7280',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Nouveau => 'bi-exclamation-circle',
            self::Valide => 'bi-patch-check',
            self::Planifie => 'bi-calendar-check',
            self::EnCours => 'bi-truck',
            self::Resolu => 'bi-check-circle',
            self::Rejete => 'bi-x-circle',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, self::open(), true);
    }

    /** @return list<self> */
    public static function open(): array
    {
        return [self::Nouveau, self::Valide, self::Planifie, self::EnCours];
    }

    /** Statuts indiquant qu'un responsable a confirmé le problème. @return list<self> */
    public static function verified(): array
    {
        return [self::Valide, self::Planifie, self::EnCours, self::Resolu];
    }
}
