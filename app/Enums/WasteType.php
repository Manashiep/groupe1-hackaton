<?php

namespace App\Enums;

enum WasteType: string
{
    case Menagers = 'menagers';
    case Recyclables = 'recyclables';
    case Verts = 'verts';
    case Encombrants = 'encombrants';

    public function label(): string
    {
        return match ($this) {
            self::Menagers => 'Ordures ménagères',
            self::Recyclables => 'Plastiques & recyclables',
            self::Verts => 'Déchets verts',
            self::Encombrants => 'Encombrants',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Menagers => 'bi-trash3',
            self::Recyclables => 'bi-recycle',
            self::Verts => 'bi-tree',
            self::Encombrants => 'bi-box-seam',
        };
    }
}
