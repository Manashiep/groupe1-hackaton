<?php

namespace App\Http\Controllers\Citizen;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Quartier;
use App\Models\Report;
use App\Notifications\CleanNotification;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('citizen.reports.index', [
            'reports' => $request->user()->reports()->with('quartier')->latest()->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        return view('citizen.reports.create', [
            'quartiers' => Quartier::orderBy('ville')->orderBy('name')->get(),
            'categories' => ReportCategory::cases(),
            'defaultQuartier' => $request->user()->quartier,
        ]);
    }

    public function store(Request $request, Notifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::enum(ReportCategory::class)],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:6144'],
            // Emprise approximative de l'archipel des Comores.
            'latitude' => ['required', 'numeric', 'between:-12.6,-11.2'],
            'longitude' => ['required', 'numeric', 'between:43.1,44.7'],
            'quartier_id' => ['required', 'exists:quartiers,id'],
            'address' => ['nullable', 'string', 'max:255'],
        ], [
            'latitude.required' => 'Indiquez l\'emplacement des déchets sur la carte.',
            'latitude.between' => 'L\'emplacement doit se situer aux Comores.',
            'longitude.between' => 'L\'emplacement doit se situer aux Comores.',
        ]);

        $report = Report::create([
            ...collect($data)->except('photo')->all(),
            'user_id' => $request->user()->id,
            'photo_path' => $request->file('photo')?->store('reports', 'public'),
            'status' => ReportStatus::Nouveau,
        ]);
        $report->events()->create([
            'user_id' => $request->user()->id,
            'status' => ReportStatus::Nouveau,
            'note' => 'Signalement envoyé par un habitant.',
        ]);

        $notifier->staff(new CleanNotification(
            'Nouveau signalement',
            "{$report->category->label()} signalé à {$report->quartier->name} ({$report->reference()}).",
            route('admin.reports.show', $report),
            'bi-geo-alt',
            'danger',
        ));

        return redirect()->route('citizen.reports.show', $report)
            ->with('success', 'Merci ! Votre signalement a été transmis au service propreté. Vous gagnerez 10 points dès qu\'il sera vérifié.');
    }

    public function show(Request $request, Report $report): View
    {
        abort_unless($report->user_id === $request->user()->id, 403);

        return view('citizen.reports.show', [
            'report' => $report->load(['quartier', 'events.user', 'interventions.team']),
        ]);
    }
}
