<?php

namespace App\Enums;

enum ReportCategory: string
{
    case DepotSauvage = 'depot_sauvage';
    case PoubelleDebordante = 'poubelle_debordante';
    case Encombrants = 'encombrants';
    case Plastiques = 'plastiques';
    case DechetsDangereux = 'dechets_dangereux';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::DepotSauvage => 'Dépôt sauvage',
            self::PoubelleDebordante => 'Point de collecte débordant',
            self::Encombrants => 'Encombrants abandonnés',
            self::Plastiques => 'Plastiques (plage, ravine)',
            self::DechetsDangereux => 'Déchets dangereux',
            self::Autre => 'Autre problème',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DepotSauvage => 'bi-trash3',
            self::PoubelleDebordante => 'bi-trash2-fill',
            self::Encombrants => 'bi-box-seam',
            self::Plastiques => 'bi-water',
            self::DechetsDangereux => 'bi-radioactive',
            self::Autre => 'bi-question-circle',
        };
    }
}
