<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Services\CollectionPlanner;
use App\Services\RouteBuilder;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, CollectionPlanner $planner, RouteBuilder $routes): View
    {
        $planner->ensureUpcoming();
        $team = $request->user()->team;
        $date = $this->date($request);
        $route = $team ? $routes->forTeam($team, $date) : null;

        return view('agent.dashboard', [
            'team' => $team,
            'date' => $date,
            'route' => $route,
            'points' => $route ? $routes->mapPoints($route['stops']) : [],
        ]);
    }

    public function planning(Request $request, CollectionPlanner $planner, RouteBuilder $routes): View
    {
        $planner->ensureUpcoming();
        $team = $request->user()->team;

        $days = $team ? collect(CarbonPeriod::create(today(), today()->addDays(6)))
            ->map(fn (Carbon $day) => ['date' => $day, ...$routes->forTeam($team, $day)]) : collect();

        return view('agent.planning', ['team' => $team, 'days' => $days]);
    }

    private function date(Request $request): Carbon
    {
        try {
            return $request->filled('date') ? Carbon::parse($request->query('date'))->startOfDay() : today();
        } catch (\Throwable) {
            return today();
        }
    }
}
