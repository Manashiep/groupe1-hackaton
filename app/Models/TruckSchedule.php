<?php

namespace App\Models;

use Database\Factories\TruckScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Créneau de ramassage : tel camion passe dans tel village tel jour à telle heure.
 */
class TruckSchedule extends Model
{
    /** @use HasFactory<TruckScheduleFactory> */
    use HasFactory;

    /** @var array<int, string> */
    public const DAYS = [
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
        5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
    ];

    protected $fillable = ['truck_id', 'village_id', 'day_of_week', 'start_time', 'end_time'];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer'];
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function dayLabel(): string
    {
        return self::DAYS[$this->day_of_week] ?? '?';
    }

    public function timeRange(): string
    {
        $start = substr($this->start_time, 0, 5);

        return $this->end_time ? $start.' – '.substr($this->end_time, 0, 5) : 'dès '.$start;
    }

    /** Prochaine date de ce créneau qui n'est pas encore terminée. */
    public function nextOccurrence(): Carbon
    {
        $candidate = today();
        while ($candidate->dayOfWeekIso !== $this->day_of_week
            || $candidate->copy()->setTimeFromTimeString($this->end_time ?? $this->start_time)->isPast()) {
            $candidate->addDay();
        }

        return $candidate;
    }
}
