<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Signalement d'un dépôt sauvage.
 */
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'village_id', 'photo_path', 'description', 'latitude', 'longitude',
        'status', 'truck_id', 'cleanup_at', 'cleaned_at', 'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'cleanup_at' => 'datetime',
            'cleaned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    /** @param Builder<Report> $query */
    public function scopeVisibleOnMap(Builder $query): void
    {
        $query->whereIn('status', ReportStatus::visibleOnMap());
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    /** « Nettoyage prévu jeudi 14 novembre à 9h00 » quand une équipe a pris le signalement en charge. */
    public function cleanupLabel(): ?string
    {
        return $this->cleanup_at
            ? 'Nettoyage prévu '.$this->cleanup_at->translatedFormat('l j F à H\hi')
            : null;
    }
}
