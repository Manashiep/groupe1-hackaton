<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Planning des 7 prochains jours : villages et horaires de ramassage du camion.
 */
class PlanningController extends Controller
{
    public function index(Request $request): View
    {
        $truck = $request->user()->truck?->load('schedules.village');

        return view('agent.planning', [
            'truck' => $truck,
            'days' => $truck
                ? collect(range(0, 6))->map(fn (int $offset): array => [
                    'date' => today()->addDays($offset),
                    'slots' => $truck->schedulesOn(today()->addDays($offset)),
                ])
                : collect(),
        ]);
    }
}
