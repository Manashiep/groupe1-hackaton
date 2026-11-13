<?php

namespace App\Http\Controllers\Citizen;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\CollectionComplaint;
use App\Models\Quartier;
use App\Models\User;
use App\Services\CleanlinessIndex;
use App\Services\CollectionPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, CollectionPlanner $planner, CleanlinessIndex $index): View
    {
        $planner->ensureUpcoming();
        $user = $request->user()->load('quartier');
        $quartier = $user->quartier;

        $collections = $quartier
            ? $quartier->collections()->with('team')
                ->whereBetween('scheduled_date', [today()->subDays(2)->toDateString(), today()->addDays(7)->toDateString()])
                ->orderBy('scheduled_date')->orderBy('start_time')->get()
            : collect();

        return view('citizen.dashboard', [
            'user' => $user,
            'quartier' => $quartier,
            'quartiers' => Quartier::orderBy('ville')->orderBy('name')->get(),
            'collections' => $collections,
            'next' => $collections->first(fn ($c) => $c->isAwaited()),
            'complainedIds' => CollectionComplaint::where('user_id', $user->id)
                ->whereIn('collection_id', $collections->pluck('id'))->pluck('collection_id')->all(),
            'reports' => $user->reports()->with('quartier')->latest()->limit(5)->get(),
            'pickups' => $user->pickupRequests()->latest()->limit(3)->get(),
            'index' => $quartier ? $index->for($quartier) : null,
            'rank' => User::where('role', Role::Citoyen)->where('points', '>', $user->points)->count() + 1,
        ]);
    }

    public function updateQuartier(Request $request): RedirectResponse
    {
        $data = $request->validate(['quartier_id' => ['required', 'exists:quartiers,id']]);
        $request->user()->update($data);

        return back()->with('success', 'Votre quartier a été mis à jour.');
    }
}
