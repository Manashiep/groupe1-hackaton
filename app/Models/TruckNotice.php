<?php

namespace App\Models;

use App\Enums\NoticeKind;
use Database\Factories\TruckNoticeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alerte de l'agent aux habitants : tournée pas terminée, retard, passage annulé.
 */
class TruckNotice extends Model
{
    /** @use HasFactory<TruckNoticeFactory> */
    use HasFactory;

    protected $fillable = ['truck_id', 'user_id', 'kind', 'message'];

    protected function casts(): array
    {
        return ['kind' => NoticeKind::class];
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Les alertes restent affichées 24 heures.
     *
     * @param  Builder<TruckNotice>  $query
     */
    public function scopeRecent(Builder $query): void
    {
        $query->where('created_at', '>=', now()->subDay());
    }
}
