<?php

namespace App\Enums;

enum PostType: string
{
    case Annonce = 'annonce';
    case Publication = 'publication';

    public function label(): string
    {
        return match ($this) {
            self::Annonce => 'Annonce',
            self::Publication => 'Publication',
        };
    }
}
