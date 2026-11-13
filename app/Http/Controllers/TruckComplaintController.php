<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintReason;
use App\Models\Truck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TruckComplaintController extends Controller
{
    public function store(Request $request, Truck $truck): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', Rule::enum(ComplaintReason::class)],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $truck->complaints()->create([...$data, 'user_id' => $request->user()->id]);

        return back()->with('success', 'Merci, la mairie a bien reçu votre signalement.');
    }
}
