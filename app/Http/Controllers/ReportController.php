<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Http\Requests\TakeChargeRequest;
use App\Models\Report;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Signalements : les habitants signalent, un citoyen ou une association s'en occupe.
 */
class ReportController extends Controller
{
    public function create(): View
    {
        return view('reports.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'photo' => ['nullable', 'image', 'max:6144'],
            'description' => ['nullable', 'string', 'max:500'],
            // Emprise approximative de l'archipel des Comores.
            'latitude' => ['required', 'numeric', 'between:-12.6,-11.2'],
            'longitude' => ['required', 'numeric', 'between:43.1,44.7'],
        ], [
            'latitude.required' => 'Indiquez l\'endroit sur la carte.',
            'latitude.between' => 'L\'endroit doit se trouver aux Comores.',
            'longitude.between' => 'L\'endroit doit se trouver aux Comores.',
        ]);

        $report = $request->user()->reports()->create([
            'description' => $data['description'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'village_id' => Village::nearestTo((float) $data['latitude'], (float) $data['longitude'])?->id,
            'photo_path' => $request->file('photo')?->store('reports', 'public'),
            'status' => ReportStatus::Nouveau,
        ]);

        return redirect()->route('reports.show', $report)->with('success', 'Merci ! Votre signalement est visible sur la carte.');
    }

    public function show(Request $request, Report $report): View
    {
        abort_if($report->status === ReportStatus::Rejete && $report->user_id !== $request->user()->id, 404);

        return view('reports.show', [
            'report' => $report->load(['village', 'taker', 'association', 'user']),
            'ledAssociation' => $request->user()->activeLedAssociation(),
        ]);
    }

    public function take(TakeChargeRequest $request, Report $report): RedirectResponse
    {
        if ($report->status !== ReportStatus::Nouveau) {
            return back()->withErrors(['planned_at' => 'Quelqu\'un s\'en occupe déjà.']);
        }

        $report->update([
            'status' => ReportStatus::PrisEnCharge,
            'taken_by_id' => $request->user()->id,
            'association_id' => $request->validated('association_id'),
            'cleanup_at' => $request->validated('planned_at'),
            'taker_note' => $request->validated('taker_note'),
        ]);

        return back()->with('success', 'Merci ! Tout le monde voit sur la carte que vous vous en occupez.');
    }

    public function start(Request $request, Report $report): RedirectResponse
    {
        $this->ensureTaker($request, $report);
        $report->update(['status' => ReportStatus::EnCours]);

        return back();
    }

    /**
     * Pas fini ? Le preneur prévient et donne une nouvelle date.
     */
    public function postpone(Request $request, Report $report): RedirectResponse
    {
        $this->ensureTaker($request, $report);
        $data = $request->validate([
            'planned_at' => ['required', 'date', 'after_or_equal:today'],
            'taker_note' => ['required', 'string', 'max:300'],
        ], ['taker_note.required' => 'Expliquez en quelques mots ce qu\'il reste à faire.']);

        $report->update([
            'status' => ReportStatus::PrisEnCharge,
            'cleanup_at' => $data['planned_at'],
            'taker_note' => $data['taker_note'],
        ]);

        return back()->with('success', 'C\'est noté, les habitants voient la nouvelle date.');
    }

    public function finish(Request $request, Report $report): RedirectResponse
    {
        $this->ensureTaker($request, $report);
        $report->update(['status' => ReportStatus::Nettoye, 'cleaned_at' => now()]);

        return back()->with('success', 'Bravo, la zone est nettoyée !');
    }

    public function release(Request $request, Report $report): RedirectResponse
    {
        $this->ensureTaker($request, $report);
        $report->update([
            'status' => ReportStatus::Nouveau,
            'taken_by_id' => null,
            'association_id' => null,
            'cleanup_at' => null,
            'taker_note' => null,
        ]);

        return back()->with('success', 'Le signalement est de nouveau disponible pour d\'autres volontaires.');
    }

    private function ensureTaker(Request $request, Report $report): void
    {
        abort_unless($report->isTakenBy($request->user()), 403);
    }
}
