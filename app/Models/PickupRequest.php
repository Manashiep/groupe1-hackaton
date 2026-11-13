<?php

namespace App\Models;

use App\Enums\PickupStatus;
use App\Models\Concerns\CanBeTakenInCharge;
use Database\Factories\PickupRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande de collecte (beaucoup de déchets, encombrants…), ramassée par un volontaire.
 */
class PickupRequest extends Model
{
    use CanBeTakenInCharge;

    /** @use HasFactory<PickupRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'village_id', 'description', 'latitude', 'longitude', 'preferred_date', 'status',
        'taken_by_id', 'association_id', 'appointment_at', 'taker_note', 'done_at', 'admin_note',
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

    public function appointmentLabel(): ?string
    {
        return $this->appointment_at ? 'Passage prévu '.$this->appointment_at->translatedFormat('l j F à H\hi') : null;
    }
}
