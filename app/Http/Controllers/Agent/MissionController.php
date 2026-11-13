<?php

namespace App\Http\Controllers\Agent;

use App\Enums\CollectionStatus;
use App\Enums\InterventionStatus;
use App\Enums\PickupStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Intervention;
use App\Notifications\CleanNotification;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Actions de terrain des agents : démarrer / confirmer un passage ou une intervention.
 */
class MissionController extends Controller
{
    public function __construct(private Notifier $notifier) {}

    public function startCollection(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorizeTeam($request, $collection->team_id);
        abort_unless($collection->status === CollectionStatus::Programmee, 422, 'Ce passage a déjà démarré.');

        $collection->update(['status' => CollectionStatus::EnCours, 'started_at' => now()]);

        $count = $this->notifier->quartierResidents($collection->quartier_id, new CleanNotification(
            'La camionnette arrive',
            "La collecte a commencé à {$collection->quartier->name}. Sortez vos sacs maintenant !",
            route('citizen.dashboard'),
            'bi-truck',
            'primary',
        ));

        return back()->with('success', "Passage démarré. {$count} habitant(s) de {$collection->quartier->name} ont été prévenus.");
    }

    public function completeCollection(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorizeTeam($request, $collection->team_id);
        $data = $request->validate(['agent_note' => ['nullable', 'string', 'max:500']]);

        $collection->update([
            'status' => CollectionStatus::Effectuee,
            'started_at' => $collection->started_at ?? now(),
            'completed_at' => now(),
            'confirmed_by' => $request->user()->id,
            'agent_note' => $data['agent_note'] ?? $collection->agent_note,
        ]);

        // Les habitants qui avaient signalé un retard sont informés que le passage a eu lieu.
        foreach ($collection->complaints()->with('user')->get() as $complaint) {
            $this->notifier->user($complaint->user, new CleanNotification(
                'Collecte effectuée',
                "La camionnette est passée à {$collection->quartier->name}. Merci de votre patience.",
                route('citizen.dashboard'),
                'bi-check2-circle',
                'success',
            ));
        }

        return back()->with('success', "Collecte de {$collection->quartier->name} confirmée.");
    }

    public function failCollection(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorizeTeam($request, $collection->team_id);
        $data = $request->validate(['agent_note' => ['required', 'string', 'max:500']], [
            'agent_note.required' => 'Expliquez brièvement pourquoi le passage n\'a pas pu être effectué.',
        ]);

        $collection->update(['status' => CollectionStatus::NonEffectuee, 'agent_note' => $data['agent_note']]);

        $this->notifier->staff(new CleanNotification(
            'Passage non effectué',
            "{$collection->team?->name} n'a pas pu collecter {$collection->quartier->name} : {$data['agent_note']}",
            route('admin.collections.index', ['date' => $collection->scheduled_date->toDateString()]),
            'bi-exclamation-triangle',
            'danger',
        ));

        return back()->with('success', 'Le problème a été transmis au responsable, qui pourra programmer un rattrapage.');
    }

    public function startIntervention(Request $request, Intervention $intervention): RedirectResponse
    {
        $this->authorizeTeam($request, $intervention->team_id);
        abort_unless($intervention->status === InterventionStatus::Planifiee, 422);

        $intervention->update(['status' => InterventionStatus::EnCours, 'started_at' => now()]);

        if ($report = $intervention->report) {
            $report->transition(ReportStatus::EnCours, $request->user(), "L'{$intervention->team->name} est sur place.");
            $this->notifier->user($report->user, new CleanNotification(
                'Nettoyage en cours',
                "Une équipe est sur place pour votre signalement {$report->reference()}.",
                route('citizen.reports.show', $report),
                'bi-truck',
                'primary',
            ));
        }

        return back()->with('success', 'Intervention démarrée.');
    }

    public function completeIntervention(Request $request, Intervention $intervention): RedirectResponse
    {
        $this->authorizeTeam($request, $intervention->team_id);
        $data = $request->validate([
            'completion_note' => ['nullable', 'string', 'max:1000'],
            'completion_photo' => ['nullable', 'image', 'max:6144'],
        ]);

        $intervention->update([
            'status' => InterventionStatus::Terminee,
            'started_at' => $intervention->started_at ?? now(),
            'completed_at' => now(),
            'completion_note' => $data['completion_note'] ?? null,
            'completion_photo_path' => $request->file('completion_photo')?->store('interventions', 'public'),
        ]);

        if ($report = $intervention->report) {
            $report->transition(ReportStatus::Resolu, $request->user(), $data['completion_note'] ?? "Zone nettoyée par l'{$intervention->team->name}.");
            $this->notifier->user($report->user, new CleanNotification(
                'Zone nettoyée',
                "Votre signalement {$report->reference()} a été traité. Merci pour votre vigilance !",
                route('citizen.reports.show', $report),
                'bi-check-circle',
                'success',
            ));
        }

        if ($pickup = $intervention->pickupRequest) {
            $pickup->update(['status' => PickupStatus::Effectuee]);
            $this->notifier->user($pickup->user, new CleanNotification(
                'Collecte exceptionnelle effectuée',
                "Votre demande {$pickup->reference()} a été réalisée.",
                route('citizen.pickups.index'),
                'bi-check-circle',
                'success',
            ));
        }

        return back()->with('success', 'Intervention terminée. Merci !');
    }

    private function authorizeTeam(Request $request, ?int $teamId): void
    {
        abort_unless($teamId && $request->user()->team_id === $teamId, 403, 'Cette mission est affectée à une autre équipe.');
    }
}
