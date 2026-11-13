<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\CollectionSchedule;
use App\Models\Quartier;
use App\Models\Report;
use App\Models\Team;
use App\Notifications\CleanNotification;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public const POINTS_PER_VERIFIED_REPORT = 10;

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'quartier', 'category', 'q']);

        $reports = Report::with(['quartier', 'user'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $status === 'ouverts'
                ? $q->open()
                : $q->where('status', $status))
            ->when($filters['quartier'] ?? null, fn ($q, $id) => $q->where('quartier_id', $id))
            ->when($filters['category'] ?? null, fn ($q, $cat) => $q->where('category', $cat))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $id = (int) preg_replace('/\D/', '', $term);
                $q->where(fn ($w) => $w->where('description', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%")
                    ->when($id, fn ($w) => $w->orWhere('id', $id)));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'filters' => $filters,
            'counts' => Report::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'),
            'quartiers' => Quartier::orderBy('name')->get(),
            'categories' => ReportCategory::cases(),
        ]);
    }

    public function show(Report $report): View
    {
        $report->load(['quartier', 'user', 'events.user', 'interventions.team']);

        // Équipe suggérée : celle qui dessert habituellement le quartier.
        $suggestedTeam = CollectionSchedule::where('quartier_id', $report->quartier_id)->value('team_id');

        return view('admin.reports.show', [
            'report' => $report,
            'teams' => Team::orderBy('name')->get(),
            'suggestedTeam' => $suggestedTeam,
            'nearby' => Report::where('id', '!=', $report->id)
                ->whereBetween('latitude', [$report->latitude - 0.0015, $report->latitude + 0.0015])
                ->whereBetween('longitude', [$report->longitude - 0.0015, $report->longitude + 0.0015])
                ->where('created_at', '>=', now()->subDays(90))
                ->latest()->limit(5)->get(),
        ]);
    }

    public function updateStatus(Request $request, Report $report, Notifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['valider', 'rejeter', 'resoudre', 'rouvrir'])],
            'note' => [Rule::requiredIf($request->input('action') === 'rejeter'), 'nullable', 'string', 'max:500'],
        ], ['note.required' => 'Indiquez le motif du rejet : il sera communiqué à l\'habitant.']);

        $user = $request->user();

        switch ($data['action']) {
            case 'valider':
                $this->verify($report, $user, $notifier, $data['note'] ?? null);
                $message = 'Signalement vérifié.';
                break;

            case 'rejeter':
                $report->transition(ReportStatus::Rejete, $user, $data['note']);
                $notifier->user($report->user, new CleanNotification(
                    'Signalement non retenu',
                    "Votre signalement {$report->reference()} n'a pas été retenu : {$data['note']}",
                    route('citizen.reports.show', $report), 'bi-x-circle', 'secondary',
                ));
                $message = 'Signalement rejeté.';
                break;

            case 'resoudre':
                $report->transition(ReportStatus::Resolu, $user, $data['note'] ?? 'Clôturé par le service propreté.');
                $notifier->user($report->user, new CleanNotification(
                    'Zone nettoyée', "Votre signalement {$report->reference()} a été traité. Merci !",
                    route('citizen.reports.show', $report), 'bi-check-circle', 'success',
                ));
                $message = 'Signalement clôturé.';
                break;

            default: // rouvrir
                $report->update(['resolved_at' => null]);
                $report->transition(ReportStatus::Valide, $user, $data['note'] ?? 'Signalement rouvert : le problème est réapparu.');
                $message = 'Signalement rouvert.';
        }

        return back()->with('success', $message);
    }

    /** Vérifie un signalement et crédite l'auteur (une seule fois). */
    public static function verify(Report $report, $by, Notifier $notifier, ?string $note = null): void
    {
        if ($report->status !== ReportStatus::Nouveau) {
            return;
        }

        $report->transition(ReportStatus::Valide, $by, $note ?? 'Signalement vérifié par le service propreté.');

        if (! $report->points_awarded && $report->user) {
            $report->user->increment('points', self::POINTS_PER_VERIFIED_REPORT);
            $report->update(['points_awarded' => true]);
        }

        $notifier->user($report->user, new CleanNotification(
            'Signalement vérifié (+'.self::POINTS_PER_VERIFIED_REPORT.' points)',
            "Votre signalement {$report->reference()} a été vérifié par le service propreté.",
            route('citizen.reports.show', $report), 'bi-patch-check', 'warning',
        ));
    }
}
