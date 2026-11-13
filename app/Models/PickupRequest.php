<?php

namespace App\Models;

use App\Enums\PickupStatus;
use Database\Factories\PickupRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande de collecte (grande quantité de déchets, encombrants…).
 */
class PickupRequest extends Model
{
    /** @use HasFactory<PickupRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'village_id', 'description', 'latitude', 'longitude', 'preferred_date',
        'status', 'truck_id', 'appointment_at', 'done_at', 'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => PickupStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'preferred_date' => 'date',
            'appointment_at' => 'datetime',
            'done_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }
}
