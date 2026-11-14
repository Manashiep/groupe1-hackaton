<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * État d'une tentative de paiement, tel que CleanComores le connaît.
 */
enum PaymentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'En attente de confirmation',
            self::Succeeded => 'Réussi',
            self::Failed => 'Échoué',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Succeeded => 'success',
            self::Failed => 'danger',
        };
    }

    /** Explication lisible des codes d'échec renvoyés par SimuPay. */
    public static function failureLabel(?string $reason): string
    {
        return match ($reason) {
            'insufficient_funds' => 'Solde insuffisant',
            'declined' => 'Paiement refusé',
            default => 'Paiement non abouti',
        };
    }
}
