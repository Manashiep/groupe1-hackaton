<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quartier extends Model
{
    protected $fillable = ['name', 'slug', 'ville', 'ile', 'latitude', 'longitude', 'population'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'population' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Quartier $quartier) {
            if (blank($quartier->slug)) {
                $quartier->slug = Str::slug($quartier->name.'-'.$quartier->ville);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(CollectionSchedule::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(User::class)->where('role', Role::Citoyen->value);
    }

    public function fullName(): string
    {
        return $this->ville === $this->name ? $this->name : "{$this->name} ({$this->ville})";
    }
}
