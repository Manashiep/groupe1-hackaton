<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Pourquoi un habitant gagne des points, et combien.
 * Les points ne sont donnés qu'une fois le dépôt nettoyé (photo à l'appui) : un faux signalement ne rapporte rien.
 */
enum PointReason: string implements HasLabel
{
    case Signalement = 'signalement';
    case Nettoyage = 'nettoyage';
    case Ramassage = 'ramassage';

    public function points(): int
    {
        return match ($this) {
            self::Signalement => 10,
            self::Nettoyage, self::Ramassage => 30,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Signalement => 'Dépôt signalé, puis nettoyé',
            self::Nettoyage => 'Dépôt nettoyé',
            self::Ramassage => 'Ramassage effectué',
        };
    }
}
