<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InterventionStatus;
use App\Enums\PickupStatus;
use App\Http\Controllers\Controller;
use App\Models\CollectionSchedule;
use App\Models\Intervention;
use App\Models\PickupRequest;
use App\Models\Team;
use App\Notifications\CleanNotification;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Demandes de collecte exceptionnelle : acceptation avec rendez-vous ou refus motivé.
 */
class PickupRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', PickupStatus::EnAttente->value);

        return view('admin.pickups.index', [
            'pickups' => PickupRequest::with(['user', 'quartier', 'intervention.team'])
                ->when($status !== 'toutes', fn ($q) => $q->where('status', $status))
                ->latest()->paginate(15)->withQueryString(),
            'status' => $status,
            'counts' => PickupRequest::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'),
            'teams' => Team::orderBy('name')->get(),
            'teamByQuartier' => CollectionSchedule::select('quartier_id', 'team_id')->get()->pluck('team_id', 'quartier_id'),
        ]);
    }

    public function accept(Request $request, PickupRequest $pickup, Notifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'appointment_at' => ['required', 'date', 'after:now'],
            'team_id' => ['required', 'exists:teams,id'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $pickup->update([
            'status' => PickupStatus::Acceptee,
            'appointment_at' => $data['appointment_at'],
            'admin_note' => $data['admin_note'] ?? null,
        ]);

        Intervention::create([
            'pickup_request_id' => $pickup->id,
            'quartier_id' => $pickup->quartier_id,
            'team_id' => $data['team_id'],
            'assigned_by' => $request->user()->id,
            'title' => 'Collecte exceptionnelle – '.($pickup->quartier?->name ?? $pickup->address),
            'notes' => $pickup->description."\nAdresse : ".$pickup->address,
            'priority' => 'normale',
            'scheduled_at' => $data['appointment_at'],
            'status' => InterventionStatus::Planifiee,
            'latitude' => $pickup->latitude ?? $pickup->quartier?->latitude,
            'longitude' => $pickup->longitude ?? $pickup->quartier?->longitude,
        ]);

        $when = $pickup->appointment_at->translatedFormat('l j F à H\hi');
        $notifier->user($pickup->user, new CleanNotification(
            'Rendez-vous fixé',
            "Votre demande {$pickup->reference()} est acceptée : passage le {$when}.",
            route('citizen.pickups.index'),
            'bi-calendar-check',
            'info',
        ));
        $notifier->team((int) $data['team_id'], new CleanNotification(
            'Nouvelle collecte exceptionnelle',
            "{$pickup->quartier?->name} – {$when}.",
            route('agent.dashboard', ['date' => $pickup->appointment_at->toDateString()]),
            'bi-box-seam',
            'info',
        ));

        return back()->with('success', "Rendez-vous fixé le {$when}. L'habitant et l'équipe sont prévenus.");
    }

    public function refuse(Request $request, PickupRequest $pickup, Notifier $notifier): RedirectResponse
    {
        $data = $request->validate(['admin_note' => ['required', 'string', 'max:500']], [
            'admin_note.required' => 'Indiquez le motif du refus : il sera communiqué au demandeur.',
        ]);

        $pickup->update(['status' => PickupStatus::Refusee, 'admin_note' => $data['admin_note']]);

        $notifier->user($pickup->user, new CleanNotification(
            'Demande non retenue',
            "Votre demande {$pickup->reference()} n'a pas pu être acceptée : {$data['admin_note']}",
            route('citizen.pickups.index'),
            'bi-x-circle',
            'secondary',
        ));

        return back()->with('success', 'Demande refusée, le demandeur est informé.');
    }
}
