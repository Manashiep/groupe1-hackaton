<?php

namespace App\Models;

use Database\Factories\RouteStopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Point de collecte de la tournée d'un camion, placé par le responsable sur la carte.
 */
class RouteStop extends Model
{
    /** @use HasFactory<RouteStopFactory> */
    use HasFactory;

    protected $fillable = ['truck_id', 'position', 'label', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(RouteStopVisit::class);
    }

    public function displayName(): string
    {
        return $this->label ?: 'Point '.$this->position;
    }
}
