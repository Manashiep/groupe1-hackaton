<?php

namespace App\Models\Concerns;

use App\Models\Association;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Signalements et demandes de collecte sont pris en charge par un citoyen,
 * éventuellement au nom de l'association qu'il dirige.
 */
trait CanBeTakenInCharge
{
    public function taker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_by_id');
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function isTakenBy(?User $user): bool
    {
        return $user !== null && $this->taken_by_id === $user->id;
    }

    /** « Moroni Propre » ou « Ahmed C. » */
    public function takerName(): ?string
    {
        return $this->association?->name ?? $this->taker?->shortName();
    }
}
