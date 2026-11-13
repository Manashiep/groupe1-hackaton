<?php

namespace App\Models;

use Database\Factories\VillageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Village extends Model
{
    /** @use HasFactory<VillageFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'ile', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Village $village): void {
            if (blank($village->slug)) {
                $village->slug = Str::slug($village->name);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function trucks(): HasMany
    {
        return $this->hasMany(Truck::class);
    }

    /**
     * Village le plus proche d'une position GPS (le signalement n'a pas besoin de demander le village).
     */
    public static function nearestTo(float $latitude, float $longitude): ?self
    {
        return static::query()
            ->get()
            ->sortBy(fn (Village $village): float => ($village->latitude - $latitude) ** 2 + (($village->longitude - $longitude) * cos(deg2rad($latitude))) ** 2)
            ->first();
    }
}
