<?php

namespace App\Models;

use App\Enums\InterventionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Intervention extends Model
{
    public const PRIORITIES = [
        'normale' => ['label' => 'Normale', 'color' => 'secondary'],
        'haute' => ['label' => 'Haute', 'color' => 'warning'],
        'urgente' => ['label' => 'Urgente', 'color' => 'danger'],
    ];

    protected $fillable = [
        'report_id', 'pickup_request_id', 'quartier_id', 'team_id', 'assigned_by', 'title', 'notes',
        'priority', 'scheduled_at', 'status', 'latitude', 'longitude', 'started_at', 'completed_at',
        'completion_note', 'completion_photo_path',
    ];

    protected function casts(): array
    {
        return [
            'status' => InterventionStatus::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function pickupRequest(): BelongsTo
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function kindLabel(): string
    {
        return $this->pickup_request_id ? 'Collecte exceptionnelle' : 'Nettoyage';
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority]['label'] ?? $this->priority;
    }

    public function priorityColor(): string
    {
        return self::PRIORITIES[$this->priority]['color'] ?? 'secondary';
    }

    public function completionPhotoUrl(): ?string
    {
        return $this->completion_photo_path ? Storage::disk('public')->url($this->completion_photo_path) : null;
    }
}
