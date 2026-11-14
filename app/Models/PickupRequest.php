<?php

namespace App\Models;

use App\Enums\PickupStatus;
use App\Enums\PointReason;
use App\Models\Concerns\CanBeTakenInCharge;
use Database\Factories\PickupRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Demande de collecte (beaucoup de déchets, encombrants…), ramassée par un volontaire.
 */
class PickupRequest extends Model
{
    use CanBeTakenInCharge;

    /** @use HasFactory<PickupRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'village_id', 'description', 'latitude', 'longitude', 'preferred_date', 'status',
        'taken_by_id', 'association_id', 'appointment_at', 'taker_note', 'done_at', 'proof_photo_path', 'admin_note',
    ];

    /** @param Builder<PickupRequest> $query */
    public function scopeVisibleOnMap(Builder $query): void
    {
        $query->whereIn('status', [PickupStatus::EnAttente, PickupStatus::PriseEnCharge]);
    }

    /**
     * Un point de la carte (habitants comme mairie) ; $url mène à la fiche de la demande dans l'espace qui l'affiche.
     *
     * @return array{kind: string, lat: float, lng: float, status: string, statusClass: string, color: string, description: ?string, photo: ?string, taker: ?string, planned: ?string, date: string, url: string}
     */
    public function toMapPoint(string $url): array
    {
        return [
            'kind' => 'pickup',
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'status' => $this->status->getLabel(),
            'statusClass' => 'st-'.$this->status->value,
            'color' => $this->status->hex(),
            'description' => $this->description,
            'photo' => null,
            'taker' => $this->takerName(),
            'planned' => $this->appointmentLabel(),
            'date' => $this->created_at->diffForHumans(),
            'url' => $url,
        ];
    }

    /**
     * Le volontaire a ramassé, photo à l'appui : la demande passe à « Effectuée » et il reçoit 30 points.
     */
    public function markDone(string $proofPhotoPath): void
    {
        DB::transaction(function () use ($proofPhotoPath): void {
            $this->update([
                'status' => PickupStatus::Effectuee,
                'done_at' => now(),
                'proof_photo_path' => $proofPhotoPath,
            ]);

            if ($this->taker) {
                PointAward::award($this->taker, PointReason::Ramassage, $this);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => PickupStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'preferred_date' => 'date',
            'appointment_at' => 'datetime',
            'done_at' => 'datetime',
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

    public function appointmentLabel(): ?string
    {
        return $this->appointment_at ? 'Passage prévu '.$this->appointment_at->translatedFormat('l j F à H\hi') : null;
    }
}
