<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = ['name', 'vehicle_plate', 'color'];

    public function agents(): HasMany
    {
        return $this->hasMany(User::class)->where('role', Role::Agent->value);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(CollectionSchedule::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }
}
