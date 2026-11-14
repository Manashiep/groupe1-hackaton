<?php

namespace App\Http\Controllers;

use App\Enums\PickupStatus;
use App\Enums\ReportStatus;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * « Moi » : redevances, engagements, signalements, demandes et associations.
 */
class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('profile', [
            'user' => $user,
            'villages' => Village::orderBy('name')->get(),
            'dues' => $user->dues()->with('passage.village', 'passage.truck')->limit(20)->get(),
            'engagedReports' => $user->takenReports()->whereIn('status', [ReportStatus::PrisEnCharge, ReportStatus::EnCours])->orderBy('cleanup_at')->get(),
            'engagedPickups' => $user->takenPickups()->where('status', PickupStatus::PriseEnCharge)->orderBy('appointment_at')->get(),
            'reports' => $user->reports()->with(['taker', 'association'])->latest()->get(),
            'pickups' => $user->pickupRequests()->with(['taker', 'association'])->latest()->get(),
            'memberships' => $user->memberships()->with('association')->whereHas('association')->get(),
            'ledAssociations' => $user->ledAssociations()->get(),
        ]);
    }

    /**
     * L'habitant indique son village de résidence : c'est là qu'il paiera la redevance.
     */
    public function updateHomeVillage(Request $request): RedirectResponse
    {
        $data = $request->validate(['home_village_id' => ['required', 'exists:villages,id']], [
            'home_village_id.required' => 'Choisissez votre village.',
        ]);

        $request->user()->update($data);

        return back()->with('success', 'Village enregistré. Vous recevrez la redevance à chaque passage du camion.');
    }
}
