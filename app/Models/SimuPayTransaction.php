<?php

namespace App\Models;

use Database\Factories\SimuPayTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Transaction côté SimuPay (la fausse passerelle). Elle n'a aucun lien direct avec les tables de
 * CleanComores : seule la « merchant_reference » permet au marchand de s'y retrouver.
 */
class SimuPayTransaction extends Model
{
    /** @use HasFactory<SimuPayTransactionFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    /** Valeur par défaut connue du modèle dès sa création (pas seulement de la base). */
    protected $attributes = ['status' => self::STATUS_PENDING];

    protected $fillable = [
        'public_id', 'amount', 'currency', 'merchant_reference', 'description', 'callback_url', 'return_url',
        'status', 'payer_phone', 'failure_reason', 'webhook_status',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'webhook_status' => 'integer'];
    }

    protected static function booted(): void
    {
        // Identifiant public impossible à deviner, comme chez les vrais opérateurs.
        static::creating(function (SimuPayTransaction $transaction): void {
            $transaction->public_id ??= 'sp_'.Str::lower(Str::random(24));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Ce que l'API renvoie au marchand (jamais les colonnes internes).
     *
     * @return array{id: string, status: string, amount: int, currency: string, reference: string, checkout_url: string}
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->public_id,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'reference' => $this->merchant_reference,
            'checkout_url' => route('simupay.checkout.show', $this),
        ];
    }
}
