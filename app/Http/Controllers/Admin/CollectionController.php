<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CollectionStatus;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\CollectionComplaint;
use App\Notifications\CleanNotification;
use App\Services\CollectionPlanner;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(Request $request, CollectionPlanner $planner): View
    {
        $planner->ensureUpcoming();

        try {
            $date = $request->filled('date') ? Carbon::parse($request->query('date'))->startOfDay() : today();
        } catch (\Throwable) {
            $date = today();
        }

        $collections = Collection::with(['quartier', 'team', 'confirmer'])->withCount('complaints')
            ->whereDate('scheduled_date', $date)
            ->orderBy('start_time')
            ->get();

        return view('admin.collections.index', [
            'date' => $date,
            'collections' => $collections,
            'byTeam' => $collections->groupBy(fn ($c) => $c->team?->name ?? 'Sans équipe'),
            'counts' => $collections->countBy(fn ($c) => $c->status->value),
            'complaints' => CollectionComplaint::with(['collection.quartier', 'user'])
                ->whereHas('collection', fn ($q) => $q->whereIn('status', [CollectionStatus::Programmee, CollectionStatus::EnCours, CollectionStatus::NonEffectuee]))
                ->where('created_at', '>=', now()->subDays(7))
                ->latest()->limit(8)->get(),
            'statuses' => CollectionStatus::cases(),
        ]);
    }

    public function update(Request $request, Collection $collection): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(CollectionStatus::class)],
            'agent_note' => ['nullable', 'string', 'max:500'],
        ]);

        $status = CollectionStatus::from($data['status']);
        $collection->update([
            'status' => $status,
            'agent_note' => $data['agent_note'] ?? $collection->agent_note,
            'started_at' => $status === CollectionStatus::EnCours ? ($collection->started_at ?? now()) : $collection->started_at,
            'completed_at' => $status === CollectionStatus::Effectuee ? ($collection->completed_at ?? now()) : null,
            'confirmed_by' => $status === CollectionStatus::Effectuee ? ($collection->confirmed_by ?? $request->user()->id) : null,
        ]);

        return back()->with('success', "Statut du passage à {$collection->quartier->name} : {$status->label()}.");
    }

    /** Programme un passage de rattrapage après une collecte manquée. */
    public function reschedule(Request $request, Collection $collection, Notifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
        ]);

        $catchUp = Collection::create([
            'quartier_id' => $collection->quartier_id,
            'team_id' => $collection->team_id,
            'scheduled_date' => $data['date'],
            'start_time' => $data['start_time'],
            'waste_type' => $collection->waste_type,
            'status' => CollectionStatus::Programmee,
            'agent_note' => 'Passage de rattrapage',
        ]);

        if ($collection->status !== CollectionStatus::Effectuee) {
            $collection->update(['status' => CollectionStatus::NonEffectuee]);
        }

        $when = $catchUp->scheduled_date->translatedFormat('l j F').' à '.$data['start_time'];
        $count = $notifier->quartierResidents($collection->quartier_id, new CleanNotification(
            'Passage de rattrapage',
            "La collecte manquée à {$collection->quartier->name} est reprogrammée {$when}.",
            route('citizen.dashboard'),
            'bi-arrow-repeat',
            'info',
        ));
        $notifier->team($collection->team_id, new CleanNotification(
            'Rattrapage ajouté à la tournée',
            "{$collection->quartier->name} – {$when}.",
            route('agent.dashboard', ['date' => $catchUp->scheduled_date->toDateString()]),
            'bi-arrow-repeat',
            'info',
        ));

        return back()->with('success', "Rattrapage programmé {$when}. {$count} habitant(s) prévenu(s).");
    }

    public function generate(CollectionPlanner $planner): RedirectResponse
    {
        $created = $planner->generate(today(), today()->addDays(CollectionPlanner::HORIZON_DAYS));

        return back()->with('success', "{$created} passage(s) ajouté(s) pour les ".CollectionPlanner::HORIZON_DAYS.' prochains jours.');
    }

    public function sendReminders(Notifier $notifier): RedirectResponse
    {
        $sent = $notifier->collectionReminders(today()->addDay());

        return back()->with('success', "Rappels envoyés : {$sent} habitant(s) prévenu(s) des collectes de demain.");
    }
}
