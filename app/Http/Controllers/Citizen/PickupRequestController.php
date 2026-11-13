<?php

namespace App\Http\Controllers\Citizen;

use App\Enums\PickupStatus;
use App\Enums\WasteType;
use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Models\Quartier;
use App\Notifications\CleanNotification;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PickupRequestController extends Controller
{
    public function index(Request $request): View
    {
        return view('citizen.pickups.index', [
            'pickups' => $request->user()->pickupRequests()->with(['quartier', 'intervention.team'])->latest()->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        return view('citizen.pickups.create', [
            'quartiers' => Quartier::orderBy('ville')->orderBy('name')->get(),
            'defaultQuartier' => $request->user()->quartier,
        ]);
    }

    public function store(Request $request, Notifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'requester_type' => ['required', Rule::in(array_keys(PickupRequest::REQUESTER_TYPES))],
            'waste_type' => ['required', Rule::enum(WasteType::class)],
            'volume' => ['required', Rule::in(array_keys(PickupRequest::VOLUMES))],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'quartier_id' => ['required', 'exists:quartiers,id'],
            'address' => ['required', 'string', 'max:255'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'latitude' => ['nullable', 'numeric', 'between:-12.6,-11.2'],
            'longitude' => ['nullable', 'numeric', 'between:43.1,44.7'],
        ]);

        $pickup = $request->user()->pickupRequests()->create([...$data, 'status' => PickupStatus::EnAttente]);

        $notifier->staff(new CleanNotification(
            'Demande de collecte exceptionnelle',
            "{$pickup->requesterLabel()} à {$pickup->quartier->name} : {$pickup->volumeLabel()} ({$pickup->waste_type->label()}).",
            route('admin.pickups.index'),
            'bi-box-seam',
            'warning',
        ));

        return redirect()->route('citizen.pickups.index')
            ->with('success', 'Votre demande est enregistrée. Le service de collecte vous proposera un rendez-vous.');
    }
}
