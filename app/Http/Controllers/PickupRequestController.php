<?php

namespace App\Http\Controllers;

use App\Enums\PickupStatus;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Demandes de collecte (grande quantité de déchets, encombrants…).
 */
class PickupRequestController extends Controller
{
    public function index(Request $request): View
    {
        return view('pickups.index', [
            'pickups' => $request->user()->pickupRequests()->with('truck')->latest()->get(),
        ]);
    }

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

        $request->user()->pickupRequests()->create([
            ...$data,
            'village_id' => Village::nearestTo((float) $data['latitude'], (float) $data['longitude'])?->id,
            'status' => PickupStatus::EnAttente,
        ]);

        return redirect()->route('pickups.index')->with('success', 'Demande envoyée. La mairie vous proposera un jour de passage.');
    }
}
