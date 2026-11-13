<?php

namespace App\Models;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Report extends Model
{
    protected $fillable = [
        'user_id', 'quartier_id', 'category', 'description', 'photo_path', 'latitude', 'longitude',
        'address', 'status', 'points_awarded', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => ReportCategory::class,
            'status' => ReportStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'points_awarded' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ReportEvent::class)->oldest()->oldest('id');
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    public function latestIntervention(): HasOne
    {
        return $this->hasOne(Intervention::class)->latestOfMany();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ReportStatus::open());
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function reference(): string
    {
        return 'SIG-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    /** Enregistre une étape dans l'historique et met à jour le statut. */
    public function transition(ReportStatus $status, ?User $by = null, ?string $note = null): void
    {
        $this->status = $status;
        if ($status === ReportStatus::Resolu) {
            $this->resolved_at = now();
        }
        $this->save();

        $this->events()->create([
            'user_id' => $by?->id,
            'status' => $status,
            'note' => $note,
        ]);
    }
}
