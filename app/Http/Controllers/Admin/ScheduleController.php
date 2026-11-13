<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WasteType;
use App\Http\Controllers\Controller;
use App\Models\CollectionSchedule;
use App\Models\Quartier;
use App\Models\Team;
use App\Services\CollectionPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Programmation des passages réguliers des camionnettes.
 */
class ScheduleController extends Controller
{
    public function __construct(private CollectionPlanner $planner) {}

    public function index(Request $request): View
    {
        $schedules = CollectionSchedule::with(['quartier', 'team'])
            ->when($request->query('team'), fn ($q, $t) => $q->where('team_id', $t))
            ->orderBy('day_of_week')->orderBy('start_time')->get();

        return view('admin.schedules.index', [
            'schedules' => $schedules,
            'grid' => $schedules->groupBy('day_of_week'),
            'quartiers' => Quartier::orderBy('name')->get(),
            'teams' => Team::orderBy('name')->get(),
            'wasteTypes' => WasteType::cases(),
            'selectedTeam' => $request->query('team'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schedule = CollectionSchedule::create($this->validated($request));
        $this->planner->refreshSchedule($schedule);

        return back()->with('success', "Passage ajouté : {$schedule->quartier->name}, le {$schedule->dayLabel()} {$schedule->timeRange()}.");
    }

    public function update(Request $request, CollectionSchedule $schedule): RedirectResponse
    {
        if ($request->has('toggle')) {
            $schedule->update(['is_active' => ! $schedule->is_active]);
        } else {
            $schedule->update($this->validated($request));
        }
        $this->planner->refreshSchedule($schedule);

        return back()->with('success', 'Calendrier mis à jour. Les prochains passages ont été recalculés.');
    }

    public function destroy(CollectionSchedule $schedule): RedirectResponse
    {
        $schedule->update(['is_active' => false]);
        $this->planner->refreshSchedule($schedule);
        $schedule->delete();

        return back()->with('success', 'Créneau supprimé du calendrier.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'quartier_id' => ['required', 'exists:quartiers,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'waste_type' => ['required', Rule::enum(WasteType::class)],
        ]);
    }
}
