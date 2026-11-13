<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\CollectionPlanner;
use App\Services\RouteBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Itinéraires de collecte : tournée ordonnée de chaque équipe pour une journée.
 */
class RouteController extends Controller
{
    public function index(Request $request, RouteBuilder $routes, CollectionPlanner $planner): View
    {
        $planner->ensureUpcoming();
        $teams = Team::orderBy('name')->get();

        try {
            $date = $request->filled('date') ? Carbon::parse($request->query('date'))->startOfDay() : today();
        } catch (\Throwable) {
            $date = today();
        }

        $selected = $teams->firstWhere('id', (int) $request->query('team')) ?? $teams->first();
        $route = $selected ? $routes->forTeam($selected, $date) : null;

        return view('admin.routes.index', [
            'teams' => $teams,
            'selected' => $selected,
            'date' => $date,
            'route' => $route,
            'points' => $route ? $routes->mapPoints($route['stops']) : [],
            'summary' => $teams->map(fn (Team $team) => ['team' => $team, ...collect($routes->forTeam($team, $date))->except('stops')->all()]),
        ]);
    }
}
