<?php

namespace App\Http\Controllers;

use App\Enums\CollectionStatus;
use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Collection;
use App\Models\Intervention;
use App\Models\Quartier;
use App\Models\Report;
use App\Models\User;
use App\Services\CleanlinessIndex;
use App\Services\CollectionPlanner;
use App\Services\HotspotDetector;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(CleanlinessIndex $index): View
    {
        $quartiers = Quartier::orderBy('ville')->orderBy('name')->get();

        return view('home', [
            'quartiers' => $quartiers,
            'stats' => [
                'reports' => Report::count(),
                'resolved' => Report::where('status', ReportStatus::Resolu)->count(),
                'collections' => Collection::where('status', CollectionStatus::Effectuee)
                    ->whereDate('scheduled_date', '>=', today()->subDays(30))->count(),
                'quartiers' => $quartiers->count(),
            ],
            'ranking' => $quartiers->map(fn ($q) => ['quartier' => $q, ...$index->for($q)])
                ->sortByDesc('score')->values(),
            'leaders' => User::where('role', Role::Citoyen)->where('points', '>', 0)
                ->with('quartier')->orderByDesc('points')->limit(5)->get(),
        ]);
    }

    public function calendar(Request $request, CollectionPlanner $planner): View
    {
        $planner->ensureUpcoming();

        $quartiers = Quartier::orderBy('ville')->orderBy('name')->get();
        $selected = $quartiers->firstWhere('slug', $request->query('quartier'))
            ?? ($request->user()?->quartier_id ? $quartiers->firstWhere('id', $request->user()->quartier_id) : null);

        return view('public.calendar', [
            'quartiers' => $quartiers,
            'selected' => $selected,
            ...($selected ? $this->calendarData($selected) : []),
        ]);
    }

    public function map(CleanlinessIndex $index, HotspotDetector $hotspots): View
    {
        $reports = Report::with('quartier:id,name')
            ->where('status', '!=', ReportStatus::Rejete)
            ->where('created_at', '>=', now()->subDays(90))
            ->latest()
            ->get();

        return view('public.map', [
            'reports' => $reports,
            'points' => $reports->map(fn (Report $r) => [
                'id' => $r->id,
                'lat' => $r->latitude,
                'lng' => $r->longitude,
                'status' => $r->status->value,
                'statusLabel' => $r->status->label(),
                'color' => $r->status->hex(),
                'open' => $r->status->isOpen(),
                'category' => $r->category->label(),
                'quartier' => $r->quartier?->name,
                'date' => $r->created_at->translatedFormat('j M Y'),
                'photo' => $r->photoUrl(),
            ])->values(),
            'zones' => Quartier::all()->map(fn ($q) => [
                'name' => $q->name,
                'url' => route('quartiers.show', $q),
                'lat' => $q->latitude,
                'lng' => $q->longitude,
                ...$index->for($q),
            ])->values(),
            'hotspots' => $hotspots->detect(),
            'statuses' => collect(ReportStatus::cases())->reject(fn ($s) => $s === ReportStatus::Rejete),
        ]);
    }

    public function quartier(Quartier $quartier, CleanlinessIndex $index, CollectionPlanner $planner): View
    {
        $planner->ensureUpcoming();

        return view('public.quartier', [
            'quartier' => $quartier,
            'index' => $index->for($quartier),
            ...$this->calendarData($quartier),
            'recentReports' => $quartier->reports()->where('status', '!=', ReportStatus::Rejete)
                ->latest()->limit(8)->get(),
            'interventions' => Intervention::with('team')->where('quartier_id', $quartier->id)
                ->where('status', 'terminee')->latest('completed_at')->limit(6)->get(),
            'categoryStats' => $quartier->reports()->where('created_at', '>=', now()->subDays(90))
                ->whereIn('status', ReportStatus::verified())
                ->selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category')
                ->mapWithKeys(fn ($total, $cat) => [ReportCategory::from($cat)->label() => $total])
                ->sortDesc(),
        ]);
    }

    /** @return array<string, mixed> */
    private function calendarData(Quartier $quartier): array
    {
        return [
            'schedules' => $quartier->schedules()->with('team')->where('is_active', true)
                ->orderBy('day_of_week')->orderBy('start_time')->get(),
            'upcoming' => $quartier->collections()->with('team')->withCount('complaints')
                ->whereBetween('scheduled_date', [today()->subDays(3)->toDateString(), today()->addDays(13)->toDateString()])
                ->orderBy('scheduled_date')->orderBy('start_time')->get(),
            'next' => $quartier->collections()->with('team')
                ->whereIn('status', [CollectionStatus::Programmee, CollectionStatus::EnCours])
                ->whereDate('scheduled_date', '>=', today())
                ->orderBy('scheduled_date')->orderBy('start_time')
                ->get()
                ->first(fn (Collection $c) => $c->isAwaited()),
        ];
    }
}
