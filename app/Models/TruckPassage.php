<?php

namespace App\Models;

use App\Enums\DueStatus;
use App\Enums\Role;
use Database\Factories\TruckPassageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Passage du camion dans un village, confirmé par l'agent. Il déclenche la redevance des habitants.
 */
class TruckPassage extends Model
{
    /** @use HasFactory<TruckPassageFactory> */
    use HasFactory;

    protected $fillable = ['truck_id', 'village_id', 'agent_id', 'passed_on'];

    protected function casts(): array
    {
        return ['passed_on' => 'date'];
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function dues(): HasMany
    {
        return $this->hasMany(Due::class);
    }

    /**
     * Enregistre le passage du jour (une seule fois) et crée la redevance de chaque habitant du village.
     * Tout se fait dans une transaction : soit le passage et toutes les redevances sont créés, soit rien.
     */
    public static function record(Truck $truck, Village $village, User $agent): self
    {
        return DB::transaction(function () use ($truck, $village, $agent): self {
            $passage = static::firstOrCreate(
                ['truck_id' => $truck->id, 'village_id' => $village->id, 'passed_on' => today()->toDateString()],
                ['agent_id' => $agent->id],
            );

            $passage->chargeResidents();

            return $passage;
        });
    }

    /**
     * Crée la redevance des habitants qui n'en ont pas encore pour ce passage.
     */
    public function chargeResidents(): void
    {
        $alreadyCharged = $this->dues()->pluck('user_id');

        User::query()
            ->where('role', Role::Citoyen)
            ->where('is_active', true)
            ->where('home_village_id', $this->village_id)
            ->whereNotIn('id', $alreadyCharged)
            ->each(fn (User $resident) => $this->dues()->create([
                'user_id' => $resident->id,
                'amount' => config('cleancomores.due_amount'),
                'status' => DueStatus::APayer,
            ]));
    }
}
