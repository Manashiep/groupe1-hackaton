<?php

namespace App\Models;

use App\Enums\CollectionStatus;
use App\Enums\WasteType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Un passage concret de camionnette dans un quartier, à une date donnée.
 */
class Collection extends Model
{
    protected $fillable = [
        'collection_schedule_id', 'quartier_id', 'team_id', 'scheduled_date', 'start_time', 'end_time',
        'waste_type', 'status', 'started_at', 'completed_at', 'confirmed_by', 'agent_note',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'status' => CollectionStatus::class,
            'waste_type' => WasteType::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(CollectionSchedule::class, 'collection_schedule_id');
    }

    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(CollectionComplaint::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('scheduled_date', '>=', today())->orderBy('scheduled_date')->orderBy('start_time');
    }

    public function startsAt(): Carbon
    {
        return $this->scheduled_date->copy()->setTimeFromTimeString($this->start_time);
    }

    public function timeLabel(): string
    {
        $start = substr($this->start_time, 0, 5);

        return $this->end_time ? $start.' – '.substr($this->end_time, 0, 5) : 'dès '.$start;
    }

    /** Un habitant peut signaler le passage manqué une heure après l'horaire prévu. */
    public function canBeReportedMissing(): bool
    {
        return in_array($this->status, [CollectionStatus::Programmee, CollectionStatus::EnCours], true)
            && now()->greaterThan($this->startsAt()->addHour());
    }

    /** Passage encore attendu par les habitants (à venir, en cours ou en léger retard). */
    public function isAwaited(): bool
    {
        return $this->status === CollectionStatus::EnCours
            || ($this->status === CollectionStatus::Programmee && $this->startsAt()->addHours(3)->isFuture());
    }

    public function isToday(): bool
    {
        return $this->scheduled_date->isToday();
    }
}
