<?php

namespace App\Http\Controllers\Agent;

use App\Enums\NoticeKind;
use App\Http\Controllers\Controller;
use App\Models\RouteStop;
use App\Models\RouteStopVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tournée du jour : villages et horaires, points de collecte posés par le responsable, alertes.
 */
class RouteController extends Controller
{
    public function index(Request $request): View
    {
        $truck = $request->user()->truck?->load(['schedules.village', 'stops', 'villages']);

        if (! $truck) {
            return view('agent.route', ['truck' => null]);
        }

        return view('agent.route', [
            'truck' => $truck,
            'slots' => $truck->schedulesOn(today()),
            'stops' => $truck->stops,
            'visited' => RouteStopVisit::whereIn('route_stop_id', $truck->stops->pluck('id'))
                ->whereDate('visit_date', today())
                ->pluck('route_stop_id')
                ->all(),
            'notices' => $truck->notices()->recent()->get(),
            'passedVillageIds' => $truck->passages()->whereDate('passed_on', today())->pluck('village_id')->all(),
            'kinds' => NoticeKind::cases(),
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
