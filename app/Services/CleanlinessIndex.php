<?php

namespace App\Services;

use App\Enums\CollectionStatus;
use App\Enums\ReportStatus;
use App\Models\Collection;
use App\Models\Quartier;
use App\Models\Report;
use Illuminate\Support\Facades\DB;

/**
 * Indice de propreté d'un quartier (0 à 100), calculé uniquement sur des données vérifiées :
 *  - 40 pts : taux de collectes effectuées sur 30 jours (confirmées par les agents) ;
 *  - 40 pts : absence de dépôts sauvages vérifiés encore ouverts (-10 par dépôt) ;
 *  - 20 pts : faible récurrence des problèmes vérifiés sur 30 jours (-4 par signalement).
 * Les signalements « nouveaux » (pas encore vérifiés) ne pénalisent pas le quartier.
 */
class CleanlinessIndex
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $cache = null;

    /** @return array<int, array{score:int,label:string,color:string,hex:string,collection_rate:?int,open:int,recent:int}> */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $since = today()->subDays(30);

        $collections = Collection::query()
            ->select('quartier_id', 'status', DB::raw('count(*) as total'))
            ->whereBetween('scheduled_date', [$since->toDateString(), today()->toDateString()])
            ->whereIn('status', [CollectionStatus::Effectuee, CollectionStatus::NonEffectuee])
            ->groupBy('quartier_id', 'status')
            ->get()
            ->groupBy('quartier_id');

        $openVerified = Report::query()
            ->whereIn('status', [ReportStatus::Valide, ReportStatus::Planifie, ReportStatus::EnCours])
            ->select('quartier_id', DB::raw('count(*) as total'))
            ->groupBy('quartier_id')
            ->pluck('total', 'quartier_id');

        $recentVerified = Report::query()
            ->whereIn('status', ReportStatus::verified())
            ->where('created_at', '>=', $since)
            ->select('quartier_id', DB::raw('count(*) as total'))
            ->groupBy('quartier_id')
            ->pluck('total', 'quartier_id');

        $result = [];
        foreach (Quartier::pluck('id') as $id) {
            $rows = $collections->get($id, collect());
            $done = (int) $rows->firstWhere('status', CollectionStatus::Effectuee)?->total;
            $missed = (int) $rows->firstWhere('status', CollectionStatus::NonEffectuee)?->total;
            $rate = ($done + $missed) > 0 ? $done / ($done + $missed) : null;

            $open = (int) ($openVerified[$id] ?? 0);
            $recent = (int) ($recentVerified[$id] ?? 0);

            $score = (int) round(
                40 * ($rate ?? 1)
                + max(0, 40 - 10 * $open)
                + max(0, 20 - 4 * $recent)
            );

            $result[$id] = [
                'score' => $score,
                ...$this->grade($score),
                'collection_rate' => $rate === null ? null : (int) round($rate * 100),
                'open' => $open,
                'recent' => $recent,
            ];
        }

        return $this->cache = $result;
    }

    /** @return array{score:int,label:string,color:string,hex:string,collection_rate:?int,open:int,recent:int} */
    public function for(Quartier $quartier): array
    {
        return $this->all()[$quartier->id] ?? ['score' => 100, ...$this->grade(100), 'collection_rate' => null, 'open' => 0, 'recent' => 0];
    }

    /** @return array{label:string,color:string,hex:string} */
    public function grade(int $score): array
    {
        return match (true) {
            $score >= 80 => ['label' => 'Propre', 'color' => 'success', 'hex' => '#16a34a'],
            $score >= 60 => ['label' => 'Correct', 'color' => 'info', 'hex' => '#0891b2'],
            $score >= 40 => ['label' => 'À surveiller', 'color' => 'warning', 'hex' => '#d97706'],
            default => ['label' => 'Critique', 'color' => 'danger', 'hex' => '#dc2626'],
        };
    }
}
