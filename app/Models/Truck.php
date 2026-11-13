<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\TruckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Camion poubelle affecté par la mairie à un village.
 */
class Truck extends Model
{
    /** @use HasFactory<TruckFactory> */
    use HasFactory;

    protected $fillable = ['name', 'plate', 'village_id', 'color'];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(User::class)->where('role', Role::Agent->value);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TruckSchedule::class)->orderBy('day_of_week')->orderBy('start_time');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('position');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function pickupRequests(): HasMany
    {
        return $this->hasMany(PickupRequest::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(TruckComplaint::class);
    }

    public function scheduleOn(Carbon $date): ?TruckSchedule
    {
        return $this->schedules->firstWhere('day_of_week', $date->dayOfWeekIso);
    }

    /**
     * Prochains jours de passage du camion.
     *
     * @return Collection<int, array{date: Carbon, schedule: TruckSchedule}>
     */
    public function upcomingPassages(int $days = 14): Collection
    {
        return collect(range(0, $days - 1))
            ->map(fn (int $offset): Carbon => today()->addDays($offset))
            ->map(fn (Carbon $date): ?array => ($schedule = $this->scheduleOn($date)) ? ['date' => $date, 'schedule' => $schedule] : null)
            ->filter()
            ->reject(fn (array $passage): bool => $passage['date']->copy()
                ->setTimeFromTimeString($passage['schedule']->end_time ?? $passage['schedule']->start_time)
                ->isPast())
            ->values();
    }
}
