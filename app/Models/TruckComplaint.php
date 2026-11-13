<?php

namespace App\Models;

use App\Enums\ComplaintReason;
use Database\Factories\TruckComplaintFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Problème signalé par un habitant sur un camion (retard, comportement…).
 */
class TruckComplaint extends Model
{
    /** @use HasFactory<TruckComplaintFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'truck_id', 'reason', 'message', 'handled_at'];

    protected function casts(): array
    {
        return [
            'reason' => ComplaintReason::class,
            'handled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }
}
