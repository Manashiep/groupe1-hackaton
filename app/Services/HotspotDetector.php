<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\Report;

/**
 * Repère les « points noirs » : endroits où des dépôts vérifiés réapparaissent
 * (au moins $min signalements dans un rayon de $radius mètres sur $days jours).
 */
class HotspotDetector
{
    /** @return list<array{lat:float,lng:float,count:int,open:int,quartier:?string,last_at:\Illuminate\Support\Carbon}> */
    public function detect(int $days = 90, int $radius = 150, int $min = 3): array
    {
        $reports = Report::with('quartier:id,name')
            ->whereIn('status', ReportStatus::verified())
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get();

        $clusters = [];
        foreach ($reports as $report) {
            foreach ($clusters as $i => $cluster) {
                if ($this->distance($cluster['lat'], $cluster['lng'], $report->latitude, $report->longitude) <= $radius) {
                    $n = $cluster['count'];
                    $clusters[$i]['lat'] = ($cluster['lat'] * $n + $report->latitude) / ($n + 1);
                    $clusters[$i]['lng'] = ($cluster['lng'] * $n + $report->longitude) / ($n + 1);
                    $clusters[$i]['count']++;
                    $clusters[$i]['open'] += $report->status->isOpen() ? 1 : 0;
                    $clusters[$i]['last_at'] = $report->created_at;

                    continue 2;
                }
            }
            $clusters[] = [
                'lat' => $report->latitude,
                'lng' => $report->longitude,
                'count' => 1,
                'open' => $report->status->isOpen() ? 1 : 0,
                'quartier' => $report->quartier?->name,
                'last_at' => $report->created_at,
            ];
        }

        return collect($clusters)
            ->filter(fn ($c) => $c['count'] >= $min)
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /** Distance en mètres (formule de haversine). */
    public function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(sqrt($a));
    }
}
