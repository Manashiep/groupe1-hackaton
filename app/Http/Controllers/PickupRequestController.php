<?php

namespace App\Http\Controllers;

use App\Enums\PickupStatus;
use App\Enums\PointReason;
use App\Http\Requests\TakeChargeRequest;
use App\Models\PickupRequest;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Demandes de collecte (beaucoup de déchets, encombrants…) ramassées par un volontaire.
 */
class PickupRequestController extends Controller
{
    public function create(): View
    {
        return view('pickups.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'min:5', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-12.6,-11.2'],
            'longitude' => ['required', 'numeric', 'between:43.1,44.7'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'latitude.required' => 'Indiquez l\'endroit sur la carte.',
        ]);

        $pickup = $request->user()->pickupRequests()->create([
            ...$data,
            'village_id' => Village::nearestTo((float) $data['latitude'], (float) $data['longitude'])?->id,
            'status' => PickupStatus::EnAttente,
        ]);

        return redirect()->route('pickups.show', $pickup)->with('success', 'Demande publiée : les volontaires et associations la voient sur la carte.');
    }

    public function show(Request $request, PickupRequest $pickup): View
    {
        abort_if($pickup->status === PickupStatus::Refusee && $pickup->user_id !== $request->user()->id, 404);

        return view('pickups.show', [
            'pickup' => $pickup->load(['village', 'taker', 'association', 'user']),
            'ledAssociation' => $request->user()->activeLedAssociation(),
        ]);
    }

    public function take(TakeChargeRequest $request, PickupRequest $pickup): RedirectResponse
    {
        if ($pickup->status !== PickupStatus::EnAttente) {
            return back()->withErrors(['planned_at' => 'Quelqu\'un s\'en occupe déjà.']);
        }

        $pickup->update([
            'status' => PickupStatus::PriseEnCharge,
            'taken_by_id' => $request->user()->id,
            'association_id' => $request->validated('association_id'),
            'appointment_at' => $request->validated('planned_at'),
            'taker_note' => $request->validated('taker_note'),
        ]);

        return back()->with('success', 'Merci ! Le demandeur voit le jour de votre passage.');
    }

    public function postpone(Request $request, PickupRequest $pickup): RedirectResponse
    {
        $this->ensureTaker($request, $pickup);
        $data = $request->validate([
            'planned_at' => ['required', 'date', 'after_or_equal:today'],
            'taker_note' => ['required', 'string', 'max:300'],
        ], ['taker_note.required' => 'Expliquez en quelques mots ce qu\'il reste à faire.']);

        $pickup->update(['appointment_at' => $data['planned_at'], 'taker_note' => $data['taker_note']]);

        return back()->with('success', 'C\'est noté, le demandeur voit la nouvelle date.');
    }

    public function finish(Request $request, PickupRequest $pickup): RedirectResponse
    {
        $this->ensureTaker($request, $pickup);
        abort_if($pickup->status === PickupStatus::Effectuee, 409);

        $request->validate(
            ['proof_photo' => ['required', 'image', 'max:6144']],
            ['proof_photo.required' => 'Ajoutez une photo une fois ramassé : c\'est la preuve de la collecte.'],
        );

        $pickup->markDone($request->file('proof_photo')->store('proofs', 'public'));

        return back()->with('success', 'Collecte faite, merci ! +'.PointReason::Ramassage->points().' points pour vous.');
    }

    public function release(Request $request, PickupRequest $pickup): RedirectResponse
    {
        $this->ensureTaker($request, $pickup);
        $pickup->update([
            'status' => PickupStatus::EnAttente,
            'taken_by_id' => null,
            'association_id' => null,
            'appointment_at' => null,
            'taker_note' => null,
        ]);

        return back()->with('success', 'La demande est de nouveau disponible pour d\'autres volontaires.');
    }

    private function ensureTaker(Request $request, PickupRequest $pickup): void
    {
        abort_unless($pickup->isTakenBy($request->user()), 403);
    }
}
