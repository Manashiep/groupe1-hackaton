<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Tentative de paiement d'un dû via la passerelle (SimuPay).
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'pending', 'provider' => 'simupay'];

    protected $fillable = ['due_id', 'user_id', 'provider', 'provider_id', 'amount', 'status', 'failure_reason'];

    protected function casts(): array
    {
        return ['status' => PaymentStatus::class, 'amount' => 'integer'];
    }

    public function due(): BelongsTo
    {
        return $this->belongsTo(Due::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Applique le résultat annoncé par la passerelle (webhook ou lecture de l'API).
     * Idempotent : si le paiement est déjà réglé, un second message identique ne change rien.
     */
    public function applyGatewayStatus(string $gatewayStatus, ?string $failureReason = null): void
    {
        if ($this->status !== PaymentStatus::Pending) {
            return;
        }

        DB::transaction(function () use ($gatewayStatus, $failureReason): void {
            if ($gatewayStatus === 'succeeded') {
                $this->update(['status' => PaymentStatus::Succeeded]);
                if (! $this->due->isPaid()) {
                    $this->due->markPaid();
                }
            } elseif ($gatewayStatus === 'failed') {
                $this->update(['status' => PaymentStatus::Failed, 'failure_reason' => $failureReason]);
            }
        });
    }

    /** Référence envoyée à SimuPay : elle nous revient dans le webhook pour retrouver ce paiement. */
    public function reference(): string
    {
        return 'pay_'.$this->id;
    }
}
