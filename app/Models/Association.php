<?php

namespace App\Models;

use App\Enums\AssociationStatus;
use App\Enums\MembershipStatus;
use Database\Factories\AssociationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Groupe de nettoyeurs créé par un citoyen après accord de la mairie.
 */
class Association extends Model
{
    /** @use HasFactory<AssociationFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'village_id', 'leader_id', 'status', 'motivation', 'admin_note', 'color',
    ];

    protected function casts(): array
    {
        return ['status' => AssociationStatus::class];
    }

    protected static function booted(): void
    {
        static::creating(function (Association $association): void {
            if (blank($association->slug)) {
                $slug = $base = Str::slug($association->name);
                for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
                    $slug = "{$base}-{$i}";
                }
                $association->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('status')
            ->withTimestamps()
            ->wherePivot('status', MembershipStatus::Membre->value);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(AssociationPost::class)->latest();
    }

    /** @param Builder<Association> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', AssociationStatus::Active);
    }

    public function isLedBy(?User $user): bool
    {
        return $user !== null && $this->leader_id === $user->id;
    }

    public function membershipOf(?User $user): ?Membership
    {
        return $user ? $this->memberships()->where('user_id', $user->id)->first() : null;
    }

    public function initials(): string
    {
        return collect(preg_split('/[\s-]+/', $this->name))
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }
}
