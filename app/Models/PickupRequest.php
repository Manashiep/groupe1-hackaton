<?php

namespace App\Models;

use App\Enums\PickupStatus;
use App\Enums\WasteType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Demande de collecte exceptionnelle (grande quantité, encombrants, restaurants, commerces).
 */
class PickupRequest extends Model
{
    public const REQUESTER_TYPES = [
        'particulier' => 'Particulier',
        'restaurant' => 'Restaurant / hôtel',
        'commerce' => 'Commerce',
        'association' => 'Association / école',
    ];

    public const VOLUMES = [
        'petit' => 'Moins de 5 sacs',
        'moyen' => 'Une charrette (≈ 1 m³)',
        'grand' => 'Plus d\'une camionnette',
    ];

    protected $fillable = [
        'user_id', 'quartier_id', 'requester_type', 'waste_type', 'volume', 'description', 'address',
        'latitude', 'longitude', 'preferred_date', 'status', 'appointment_at', 'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => PickupStatus::class,
            'waste_type' => WasteType::class,
            'preferred_date' => 'date',
            'appointment_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }

    public function intervention(): HasOne
    {
        return $this->hasOne(Intervention::class)->latestOfMany();
    }

    public function requesterLabel(): string
    {
        return self::REQUESTER_TYPES[$this->requester_type] ?? $this->requester_type;
    }

    public function volumeLabel(): string
    {
        return self::VOLUMES[$this->volume] ?? $this->volume;
    }

    public function reference(): string
    {
        return 'DEM-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }
}
