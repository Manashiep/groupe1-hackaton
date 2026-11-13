<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InterventionStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Intervention;
use App\Models\Report;
use App\Models\Team;
use App\Notifications\CleanNotification;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InterventionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'team', 'date']);

        return view('admin.interventions.index', [
            'interventions' => Intervention::with(['team', 'quartier', 'report', 'pickupRequest'])
                ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
                ->when($filters['team'] ?? null, fn ($q, $t) => $q->where('team_id', $t))
                ->when($filters['date'] ?? null, fn ($q, $d) => $q->whereDate('scheduled_at', $d))
                ->orderByRaw("FIELD(status, 'en_cours', 'planifiee', 'terminee', 'annulee')")
                ->orderBy('scheduled_at')
                ->paginate(20)
                ->withQueryString(),
            'teams' => Team::orderBy('name')->get(),
            'filters' => $filters,
            'statuses' => InterventionStatus::cases(),
        ]);
    }

    public function storeForReport(Request $request, Report $report, Notifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'scheduled_at' => ['required', 'date'],
            'priority' => ['required', Rule::in(array_keys(Intervention::PRIORITIES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Planifier une intervention vaut vérification du signalement.
        ReportController::verify($report, $request->user(), $notifier);

        $team = Team::findOrFail($data['team_id']);
        $intervention = Intervention::create([
            ...$data,
            'report_id' => $report->id,
            'quartier_id' => $report->quartier_id,
            'assigned_by' => $request->user()->id,
            'title' => $report->category->label().' – '.($report->quartier?->name ?? 'zone signalée'),
            'status' => InterventionStatus::Planifiee,
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
        ]);

        $when = $intervention->scheduled_at->translatedFormat('l j F à H\hi');
        $report->transition(ReportStatus::Planifie, $request->user(), "Intervention planifiée le {$when} – {$team->name}.");

        $notifier->team($team->id, new CleanNotification(
            'Nouvelle mission',
            "{$intervention->title} – {$when}.",
            route('agent.dashboard', ['date' => $intervention->scheduled_at->toDateString()]),
            'bi-clipboard-check',
            'info',
        ));
        $notifier->user($report->user, new CleanNotification(
            'Intervention planifiée',
            "Une équipe interviendra le {$when} pour votre signalement {$report->reference()}.",
            route('citizen.reports.show', $report),
            'bi-calendar-check',
            'info',
        ));

        return back()->with('success', "Intervention affectée à l'{$team->name} pour le {$when}.");
    }

    public function update(Request $request, Intervention $intervention, Notifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'team_id' => ['sometimes', 'required', 'exists:teams,id'],
            'scheduled_at' => ['sometimes', 'required', 'date'],
            'priority' => ['sometimes', 'required', Rule::in(array_keys(Intervention::PRIORITIES))],
            'cancel' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('cancel')) {
            $intervention->update(['status' => InterventionStatus::Annulee]);
            if ($intervention->report && $intervention->report->status === ReportStatus::Planifie) {
                $intervention->report->transition(ReportStatus::Valide, $request->user(), 'Intervention annulée, à replanifier.');
            }

            return back()->with('success', 'Intervention annulée.');
        }

        $intervention->update(collect($data)->except('cancel')->all());

        if (isset($data['team_id']) || isset($data['scheduled_at'])) {
            $notifier->team($intervention->team_id, new CleanNotification(
                'Mission mise à jour',
                "{$intervention->title} – ".Carbon::parse($intervention->scheduled_at)->translatedFormat('l j F à H\hi').'.',
                route('agent.dashboard', ['date' => $intervention->scheduled_at->toDateString()]),
                'bi-clipboard-check',
                'info',
            ));
        }

        return back()->with('success', 'Intervention mise à jour.');
    }
}
