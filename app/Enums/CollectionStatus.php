<?php

namespace App\Enums;

enum CollectionStatus: string
{
    case Programmee = 'programmee';
    case EnCours = 'en_cours';
    case Effectuee = 'effectuee';
    case NonEffectuee = 'non_effectuee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Programmee => 'Programmée',
            self::EnCours => 'En cours',
            self::Effectuee => 'Effectuée',
            self::NonEffectuee => 'Non effectuée',
            self::Annulee => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Programmee => 'secondary',
            self::EnCours => 'primary',
            self::Effectuee => 'success',
            self::NonEffectuee => 'danger',
            self::Annulee => 'dark',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Programmee => 'bi-clock',
            self::EnCours => 'bi-truck',
            self::Effectuee => 'bi-check2-circle',
            self::NonEffectuee => 'bi-exclamation-triangle',
            self::Annulee => 'bi-slash-circle',
        };
    }
}
