<?php

namespace App\Models;

use App\Enums\WasteType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionSchedule extends Model
{
    public const DAYS = [
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
        5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
    ];

    protected $fillable = [
        'quartier_id', 'team_id', 'day_of_week', 'start_time', 'end_time', 'waste_type', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'waste_type' => WasteType::class,
            'is_active' => 'boolean',
        ];
    }

    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function dayLabel(): string
    {
        return self::DAYS[$this->day_of_week] ?? '?';
    }

    public function timeRange(): string
    {
        $start = substr($this->start_time, 0, 5);

        return $this->end_time ? $start.' – '.substr($this->end_time, 0, 5) : 'à partir de '.$start;
    }
}
