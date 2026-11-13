<?php

namespace App\Http\Controllers\Agent;

use App\Enums\PickupStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Planning des 7 prochains jours : jours de tournée et missions affectées au camion.
 */
class PlanningController extends Controller
{
    public function index(Request $request): View
    {
        $truck = $request->user()->truck?->load(['schedules', 'village']);

        if (! $truck) {
            return view('agent.planning', ['truck' => null, 'days' => collect()]);
        }

        $end = today()->addDays(6)->endOfDay();
        $reports = $truck->reports()->whereIn('status', [ReportStatus::PrisEnCharge, ReportStatus::EnCours])
            ->whereBetween('cleanup_at', [today(), $end])->get();
        $pickups = $truck->pickupRequests()->where('status', PickupStatus::Acceptee)
            ->whereBetween('appointment_at', [today(), $end])->get();

        $days = collect(range(0, 6))->map(function (int $offset) use ($truck, $reports, $pickups): array {
            $date = today()->addDays($offset);

            return [
                'date' => $date,
                'schedule' => $truck->scheduleOn($date),
                'missions' => collect()
                    ->concat($reports->filter(fn ($r): bool => $r->cleanup_at->isSameDay($date))
                        ->map(fn ($r): array => ['time' => $r->cleanup_at, 'label' => 'Nettoyage d\'un dépôt sauvage']))
                    ->concat($pickups->filter(fn ($p): bool => $p->appointment_at->isSameDay($date))
                        ->map(fn ($p): array => ['time' => $p->appointment_at, 'label' => 'Collecte sur demande']))
                    ->sortBy(fn (array $mission): Carbon => $mission['time'])
                    ->values(),
            ];
        });

        return view('agent.planning', ['truck' => $truck, 'days' => $days]);
    }
}
