<?php

namespace App\Http\Controllers\Agent;

use App\Enums\PickupStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\RouteStop;
use App\Models\RouteStopVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tournée du jour de l'agent : points de collecte placés par le responsable et missions de nettoyage.
 */
class RouteController extends Controller
{
    public function index(Request $request): View
    {
        $truck = $request->user()->truck?->load(['schedules', 'stops', 'village']);

        if (! $truck) {
            return view('agent.route', ['truck' => null]);
        }

        $visited = RouteStopVisit::whereIn('route_stop_id', $truck->stops->pluck('id'))
            ->whereDate('visit_date', today())
            ->pluck('route_stop_id')
            ->all();

        return view('agent.route', [
            'truck' => $truck,
            'schedule' => $truck->scheduleOn(today()),
            'stops' => $truck->stops,
            'visited' => $visited,
            'reports' => $truck->reports()
                ->whereIn('status', [ReportStatus::PrisEnCharge, ReportStatus::EnCours])
                ->whereDate('cleanup_at', '<=', today())
                ->orderBy('cleanup_at')
                ->get(),
            'pickups' => $truck->pickupRequests()
                ->where('status', PickupStatus::Acceptee)
                ->whereDate('appointment_at', '<=', today())
                ->orderBy('appointment_at')
                ->get(),
        ]);
    }

    /**
     * Coche ou décoche un point de collecte pour aujourd'hui.
     */
    public function toggleStop(Request $request, RouteStop $stop): RedirectResponse
    {
        abort_unless($stop->truck_id === $request->user()->truck_id, 403);

        $visit = $stop->visits()->whereDate('visit_date', today())->first();

        if ($visit) {
            $visit->delete();
        } else {
            $stop->visits()->create(['user_id' => $request->user()->id, 'visit_date' => today()]);
        }

        return back();
    }
}
