<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasColor, HasLabel
{
    case Citoyen = 'citoyen';
    case Agent = 'agent';
    case Responsable = 'responsable';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Citoyen => 'Citoyen',
            self::Agent => 'Agent de collecte',
            self::Responsable => 'Responsable municipal',
            self::Admin => 'Administrateur',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Citoyen => 'gray',
            self::Agent => 'info',
            self::Responsable => 'primary',
            self::Admin => 'warning',
        };
    }

    /** Responsables et administrateurs ont accès au panneau Filament. */
    public function isStaff(): bool
    {
        return $this === self::Responsable || $this === self::Admin;
    }
}
