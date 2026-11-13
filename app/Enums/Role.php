<?php

namespace App\Enums;

enum Role: string
{
    case Citoyen = 'citoyen';
    case Agent = 'agent';
    case Responsable = 'responsable';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Citoyen => 'Citoyen',
            self::Agent => 'Agent de collecte',
            self::Responsable => 'Responsable municipal',
            self::Admin => 'Administrateur',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Citoyen => 'secondary',
            self::Agent => 'info',
            self::Responsable => 'primary',
            self::Admin => 'dark',
        };
    }

    public function homeRoute(): string
    {
        return match ($this) {
            self::Citoyen => 'citizen.dashboard',
            self::Agent => 'agent.dashboard',
            self::Responsable, self::Admin => 'admin.dashboard',
        };
    }
}
