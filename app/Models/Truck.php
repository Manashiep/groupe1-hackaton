<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\TruckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Camion poubelle de la mairie : il ne passe qu'aux heures de son calendrier, dans ses villages.
 */
class Truck extends Model
{
    /** @use HasFactory<TruckFactory> */
    use HasFactory;

    protected $fillable = ['name', 'plate', 'color'];

    public function villages(): BelongsToMany
    {
        return $this->belongsToMany(Village::class)->orderBy('name');
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

    public function complaints(): HasMany
    {
        return $this->hasMany(TruckComplaint::class);
    }

    public function passages(): HasMany
    {
        return $this->hasMany(TruckPassage::class);
    }

    public function notices(): HasMany
    {
        return $this->hasMany(TruckNotice::class)->latest();
    }

    /**
     * Créneaux du camion pour une journée (un par village desservi).
     *
     * @return Collection<int, TruckSchedule>
     */
    public function schedulesOn(Carbon $date): Collection
    {
        return $this->schedules->where('day_of_week', $date->dayOfWeekIso)->sortBy('start_time')->values();
    }
}
