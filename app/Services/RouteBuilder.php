<?php

namespace App\Services;

use App\Enums\CollectionStatus;
use App\Enums\InterventionStatus;
use App\Models\Collection;
use App\Models\Intervention;
use App\Models\Team;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Construit l'itinéraire d'une équipe pour une journée : passages réguliers + interventions,
 * triés par horaire, avec la distance estimée entre les arrêts.
 */
class RouteBuilder
{
    public function __construct(private HotspotDetector $geo) {}

    /**
     * @return array{stops: SupportCollection<int, array<string, mixed>>, distance_km: float, done: int, total: int}
     */
    public function forTeam(Team $team, Carbon $date): array
    {
        $collections = Collection::with('quartier')->withCount('complaints')
            ->where('team_id', $team->id)
            ->whereDate('scheduled_date', $date)
            ->where('status', '!=', CollectionStatus::Annulee)
            ->get()
            ->map(fn (Collection $c) => [
                'type' => 'collection',
                'model' => $c,
                'time' => substr($c->start_time, 0, 5),
                'title' => 'Collecte – '.$c->quartier->name,
                'subtitle' => $c->waste_type->label().' · '.$c->timeLabel(),
                'lat' => $c->quartier->latitude,
                'lng' => $c->quartier->longitude,
                'status_label' => $c->status->label(),
                'status_color' => $c->status->color(),
                'done' => in_array($c->status, [CollectionStatus::Effectuee, CollectionStatus::NonEffectuee], true),
            ]);

        $interventions = Intervention::with(['quartier', 'report', 'pickupRequest'])
            ->where('team_id', $team->id)
            ->whereDate('scheduled_at', $date)
            ->where('status', '!=', InterventionStatus::Annulee)
            ->get()
            ->map(fn (Intervention $i) => [
                'type' => 'intervention',
                'model' => $i,
                'time' => $i->scheduled_at->format('H:i'),
                'title' => $i->title,
                'subtitle' => $i->kindLabel().' · priorité '.mb_strtolower($i->priorityLabel()),
                'lat' => $i->latitude ?? $i->quartier?->latitude,
                'lng' => $i->longitude ?? $i->quartier?->longitude,
                'status_label' => $i->status->label(),
                'status_color' => $i->status->color(),
                'done' => $i->status === InterventionStatus::Terminee,
            ]);

        $stops = $collections->concat($interventions)->sortBy('time')->values();

        $distance = 0.0;
        for ($k = 1; $k < $stops->count(); $k++) {
            $a = $stops[$k - 1];
            $b = $stops[$k];
            if ($a['lat'] && $b['lat']) {
                $distance += $this->geo->distance($a['lat'], $a['lng'], $b['lat'], $b['lng']);
            }
        }

        return [
            'stops' => $stops,
            'distance_km' => round($distance / 1000 * 1.3, 1), // ×1,3 : les routes ne sont pas en ligne droite
            'done' => $stops->where('done', true)->count(),
            'total' => $stops->count(),
        ];
    }

    /** Données JSON pour la carte Leaflet. */
    public function mapPoints(SupportCollection $stops): array
    {
        return $stops->filter(fn ($s) => $s['lat'])->values()->map(fn ($s, $i) => [
            'n' => $i + 1,
            'lat' => $s['lat'],
            'lng' => $s['lng'],
            'title' => $s['title'],
            'time' => $s['time'],
            'status' => $s['status_label'],
            'done' => $s['done'],
            'type' => $s['type'],
        ])->all();
    }
}
