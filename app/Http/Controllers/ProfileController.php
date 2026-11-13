<?php

namespace App\Http\Controllers;

use App\Enums\PickupStatus;
use App\Enums\ReportStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * « Moi » : mes engagements, mes signalements, mes demandes et mes associations.
 */
class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('profile', [
            'user' => $user,
            'engagedReports' => $user->takenReports()->whereIn('status', [ReportStatus::PrisEnCharge, ReportStatus::EnCours])->orderBy('cleanup_at')->get(),
            'engagedPickups' => $user->takenPickups()->where('status', PickupStatus::PriseEnCharge)->orderBy('appointment_at')->get(),
            'reports' => $user->reports()->with(['taker', 'association'])->latest()->get(),
            'pickups' => $user->pickupRequests()->with(['taker', 'association'])->latest()->get(),
            'memberships' => $user->memberships()->with('association')->whereHas('association')->get(),
            'ledAssociations' => $user->ledAssociations()->get(),
        ]);
    }
}
