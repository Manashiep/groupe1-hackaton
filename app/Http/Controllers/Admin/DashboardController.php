<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CollectionStatus;
use App\Enums\InterventionStatus;
use App\Enums\PickupStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Intervention;
use App\Models\PickupRequest;
use App\Models\Quartier;
use App\Models\Report;
use App\Models\Team;
use App\Services\CleanlinessIndex;
use App\Services\CollectionPlanner;
use App\Services\HotspotDetector;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(CleanlinessIndex $index, HotspotDetector $hotspots, CollectionPlanner $planner): View
    {
        $planner->ensureUpcoming();
        $since = now()->subDays(30);

        $reports30 = Report::where('created_at', '>=', $since)->count();
        $reportsPrev = Report::whereBetween('created_at', [now()->subDays(60), $since])->count();

        $collections30 = Collection::whereBetween('scheduled_date', [$since->toDateString(), today()->toDateString()])
            ->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $done = (int) ($collections30[CollectionStatus::Effectuee->value] ?? 0);
        $missed = (int) ($collections30[CollectionStatus::NonEffectuee->value] ?? 0);

        // Signalements reçus / résolus par semaine (8 dernières semaines).
        $weeks = collect(range(7, 0))->map(function ($ago) {
            $start = now()->startOfWeek()->subWeeks($ago);
            $end = $start->copy()->endOfWeek();

            return [
                'label' => $start->translatedFormat('j M'),
                'received' => Report::whereBetween('created_at', [$start, $end])->count(),
                'resolved' => Report::whereBetween('resolved_at', [$start, $end])->count(),
            ];
        });

        $quartiers = Quartier::withCount([
            'reports as reports_90' => fn ($q) => $q->where('created_at', '>=', now()->subDays(90))->where('status', '!=', ReportStatus::Rejete),
            'reports as open_reports' => fn ($q) => $q->whereIn('status', ReportStatus::open()),
        ])->get()->map(fn ($q) => ['quartier' => $q, ...$index->for($q)]);

        $todayByTeam = Team::withCount([
            'collections as today_total' => fn ($q) => $q->whereDate('scheduled_date', today())->where('status', '!=', CollectionStatus::Annulee),
            'collections as today_done' => fn ($q) => $q->whereDate('scheduled_date', today())->where('status', CollectionStatus::Effectuee),
            'collections as today_running' => fn ($q) => $q->whereDate('scheduled_date', today())->where('status', CollectionStatus::EnCours),
        ])->get();

        return view('admin.dashboard', [
            'kpis' => [
                'reports30' => $reports30,
                'reportsDelta' => $reportsPrev ? (int) round(($reports30 - $reportsPrev) / $reportsPrev * 100) : null,
                'open' => Report::open()->count(),
                'new' => Report::where('status', ReportStatus::Nouveau)->count(),
                'collectionsDone' => $done,
                'collectionRate' => ($done + $missed) ? (int) round($done / ($done + $missed) * 100) : null,
                'missed' => $missed,
                'interventionsDone' => Intervention::where('status', InterventionStatus::Terminee)->where('completed_at', '>=', $since)->count(),
                'interventionsPlanned' => Intervention::whereIn('status', [InterventionStatus::Planifiee, InterventionStatus::EnCours])->count(),
                'pickupsPending' => PickupRequest::where('status', PickupStatus::EnAttente)->count(),
            ],
            'weeks' => $weeks,
            'zones' => $quartiers->sortByDesc(fn ($z) => $z['quartier']->reports_90)->take(8)->values(),
            'ranking' => $quartiers->sortBy('score')->values(),
            'hotspots' => array_slice($hotspots->detect(), 0, 6),
            'toVerify' => Report::with('quartier')->where('status', ReportStatus::Nouveau)->latest()->limit(5)->get(),
            'todayByTeam' => $todayByTeam,
            'mapPoints' => Report::open()->get()->map(fn (Report $r) => [
                'lat' => $r->latitude, 'lng' => $r->longitude, 'color' => $r->status->hex(),
                'label' => $r->reference().' · '.$r->category->label(), 'status' => $r->status->label(),
                'url' => route('admin.reports.show', $r),
            ])->values(),
        ]);
    }
}
