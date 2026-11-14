<?php

namespace App\Models;

use App\Enums\DueStatus;
use Database\Factories\DueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Redevance due par un habitant pour un passage du camion dans son village.
 */
class Due extends Model
{
    /** @use HasFactory<DueFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'truck_passage_id', 'amount', 'status', 'receipt_code', 'paid_at'];

    protected function casts(): array
    {
        return [
            'status' => DueStatus::class,
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(TruckPassage::class, 'truck_passage_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function isPaid(): bool
    {
        return $this->status === DueStatus::Payee;
    }

    /**
     * Marque le dû payé et lui donne un code de preuve unique (ex. « CC-7K4Q9P »),
     * que l'habitant montre au camionneur. Sans 0/O ni 1/I pour éviter les confusions à l'oral.
     */
    public function markPaid(): void
    {
        do {
            $code = 'CC-'.collect(range(1, 6))->map(fn (): string => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'[random_int(0, 31)])->implode('');
        } while (static::where('receipt_code', $code)->exists());

        $this->update(['status' => DueStatus::Payee, 'receipt_code' => $code, 'paid_at' => now()]);
    }

    /** « 300 KMF » */
    public function amountLabel(): string
    {
        return number_format($this->amount, 0, ',', ' ').' '.config('cleancomores.currency');
    }
}
