<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'quartier_id', 'team_id', 'points', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'points' => 'integer',
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

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /** Responsable municipal ou administrateur : accès à l'espace de gestion. */
    public function isStaff(): bool
    {
        return $this->hasRole(Role::Responsable, Role::Admin);
    }

    public function isAgent(): bool
    {
        return $this->role === Role::Agent;
    }

    public function isCitizen(): bool
    {
        return $this->role === Role::Citoyen;
    }

    public function homeUrl(): string
    {
        return route($this->role->homeRoute());
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
