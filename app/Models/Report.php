<?php

namespace App\Models;

use App\Enums\PointReason;
use App\Enums\ReportStatus;
use App\Models\Concerns\CanBeTakenInCharge;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Signalement d'un dépôt sauvage, nettoyé par un citoyen ou une association.
 */
class Report extends Model
{
    use CanBeTakenInCharge;

    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'village_id', 'photo_path', 'description', 'latitude', 'longitude', 'status',
        'taken_by_id', 'association_id', 'cleanup_at', 'taker_note', 'cleaned_at', 'proof_photo_path', 'admin_note',
    ];

    /** Un dépôt nettoyé reste sur la carte (en vert) pendant ce nombre d'heures, puis disparaît. */
    public const CLEANED_VISIBLE_HOURS = 24;

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

    /** @param Builder<Report> $query */
    public function scopeVisibleOnMap(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereIn('status', ReportStatus::visibleOnMap())
            ->orWhere(fn (Builder $query) => $query
                ->where('status', ReportStatus::Nettoye)
                ->where('cleaned_at', '>=', now()->subHours(self::CLEANED_VISIBLE_HOURS))));
    }

    /**
     * Un point de la carte (habitants comme mairie) ; $url mène à la fiche du dépôt dans l'espace qui l'affiche.
     *
     * @return array{kind: string, lat: float, lng: float, status: string, statusClass: string, color: string, description: ?string, photo: ?string, taker: ?string, planned: ?string, date: string, url: string}
     */
    public function toMapPoint(string $url): array
    {
        return [
            'kind' => 'report',
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'status' => $this->status->getLabel(),
            'statusClass' => 'st-'.$this->status->value,
            'color' => $this->status->hex(),
            'description' => $this->description,
            // Une fois nettoyé, on montre la photo « après » : c'est la preuve visible par tous.
            'photo' => $this->proofPhotoUrl() ?? $this->photoUrl(),
            'taker' => $this->takerName(),
            'planned' => $this->cleanupLabel(),
            'date' => $this->created_at->diffForHumans(),
            'url' => $url,
        ];
    }

    /**
     * Le preneur a nettoyé, photo à l'appui : le dépôt passe à « Nettoyé »
     * et chacun reçoit ses points (signaleur 10, nettoyeur 30), le tout en une seule fois.
     */
    public function markCleaned(string $proofPhotoPath): void
    {
        DB::transaction(function () use ($proofPhotoPath): void {
            $this->update([
                'status' => ReportStatus::Nettoye,
                'cleaned_at' => now(),
                'proof_photo_path' => $proofPhotoPath,
            ]);

            if ($this->user) {
                PointAward::award($this->user, PointReason::Signalement, $this);
            }

            if ($this->taker) {
                PointAward::award($this->taker, PointReason::Nettoyage, $this);
            }
        });
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    /** « Nettoyage prévu samedi 15 novembre à 8h00 » une fois pris en charge. */
    public function cleanupLabel(): ?string
    {
        return $this->cleanup_at ? 'Nettoyage prévu '.$this->cleanup_at->translatedFormat('l j F à H\hi') : null;
    }
}
