<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        return view('admin.teams.index', [
            'teams' => Team::with(['agents', 'schedules.quartier'])
                ->withCount(['interventions as open_interventions' => fn ($q) => $q->whereIn('status', ['planifiee', 'en_cours'])])
                ->orderBy('name')->get(),
            'unassigned' => User::where('role', Role::Agent)->whereNull('team_id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.teams.form', ['team' => new Team(['color' => '#0f766e']), 'agents' => $this->agents()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $team = Team::create($this->validated($request));
        $this->syncAgents($team, $request->input('agents', []));

        return redirect()->route('admin.teams.index')->with('success', "Équipe « {$team->name} » créée.");
    }

    public function edit(Team $team): View
    {
        return view('admin.teams.form', ['team' => $team->load('agents'), 'agents' => $this->agents()]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $team->update($this->validated($request));
        $this->syncAgents($team, $request->input('agents', []));

        return redirect()->route('admin.teams.index')->with('success', "Équipe « {$team->name} » mise à jour.");
    }

    public function destroy(Team $team): RedirectResponse
    {
        $team->delete();

        return back()->with('success', 'Équipe supprimée. Ses créneaux et missions sont à réaffecter.');
    }

    private function agents()
    {
        return User::where('role', Role::Agent)->with('team')->orderBy('name')->get();
    }

    /** @param array<int|string> $agentIds */
    private function syncAgents(Team $team, array $agentIds): void
    {
        User::where('role', Role::Agent)->where('team_id', $team->id)->whereNotIn('id', $agentIds)->update(['team_id' => null]);
        User::where('role', Role::Agent)->whereIn('id', $agentIds)->update(['team_id' => $team->id]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'vehicle_plate' => ['nullable', 'string', 'max:30'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'agents' => ['array'],
            'agents.*' => ['integer', 'exists:users,id'],
        ]);

        return collect($data)->except('agents')->all();
    }
}
