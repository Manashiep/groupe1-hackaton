<?php

namespace App\Models;

use App\Enums\PointReason;
use Database\Factories\PointAwardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

/**
 * Une ligne du carnet de points.
 */
class PointAward extends Model
{
    /** @use HasFactory<PointAwardFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'reason', 'points', 'awardable_type', 'awardable_id'];

    protected function casts(): array
    {
        return [
            'reason' => PointReason::class,
            'points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function awardable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Donne les points une seule fois : si la ligne existe déjà, rien ne change.
     */
    public static function award(User $user, PointReason $reason, Model $awardable): self
    {
        return static::firstOrCreate([
            'user_id' => $user->id,
            'reason' => $reason,
            'awardable_type' => $awardable->getMorphClass(),
            'awardable_id' => $awardable->getKey(),
        ], ['points' => $reason->points()]);
    }

    /**
     * Classement du mois en cours : total de chaque habitant actif, du plus haut au plus bas.
     *
     * @return Collection<int, array{user: User, points: int, rank: int}>
     */
    public static function monthlyRanking(): Collection
    {
        $totals = static::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->selectRaw('user_id, SUM(points) as total, MAX(created_at) as last_award_at')
            ->groupBy('user_id')
            // À égalité, celui qui a atteint son score le premier passe devant.
            ->orderByDesc('total')
            ->orderBy('last_award_at')
            ->with('user')
            ->get();

        return $totals->values()->map(fn (self $row, int $index): array => [
            'user' => $row->user,
            'points' => (int) $row->total,
            'rank' => $index + 1,
        ]);
    }
}
