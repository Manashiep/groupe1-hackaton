<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'truck_id', 'village_id', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    /** Camion de l'agent de collecte. */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    /** Dernier village consulté dans le calendrier (pré-sélection). */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function pickupRequests(): HasMany
    {
        return $this->hasMany(PickupRequest::class);
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAgent(): bool
    {
        return $this->role === Role::Agent;
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->isStaff();
    }

    public function homeUrl(): string
    {
        return match (true) {
            $this->isStaff() => url('/admin'),
            $this->isAgent() => route('agent.route'),
            default => route('map'),
        };
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
