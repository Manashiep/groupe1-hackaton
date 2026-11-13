<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.index', [
            'reports' => $request->user()->reports()->with('truck')->latest()->get(),
        ]);
    }

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

        $request->user()->reports()->create([
            'description' => $data['description'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'village_id' => Village::nearestTo((float) $data['latitude'], (float) $data['longitude'])?->id,
            'photo_path' => $request->file('photo')?->store('reports', 'public'),
            'status' => ReportStatus::Nouveau,
        ]);

        return redirect()->route('reports.index')->with('success', 'Merci ! Votre signalement a été envoyé à la mairie.');
    }
}
