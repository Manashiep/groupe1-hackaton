<?php

namespace App\Http\Controllers;

use App\Enums\AssociationStatus;
use App\Enums\MembershipStatus;
use App\Models\Association;
use App\Models\AssociationPost;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Associations de nettoyage : fil d'actualité, annuaire, demande de création et adhésion.
 */
class AssociationController extends Controller
{
    /** @var list<string> */
    public const COLORS = ['#1d6b47', '#0e7490', '#2563a8', '#a16207', '#b4532a', '#7e3b62'];

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('associations.index', [
            'tab' => $request->query('onglet') === 'annuaire' ? 'annuaire' : 'actu',
            'posts' => AssociationPost::with('association')
                ->whereHas('association', fn ($q) => $q->active())
                ->latest()
                ->limit(30)
                ->get(),
            'associations' => Association::active()->with('village')->withCount('members')->orderBy('name')->get(),
            'myStatuses' => $user->memberships()->pluck('status', 'association_id'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('associations.create', [
            'villages' => Village::orderBy('name')->get(),
            'pending' => $request->user()->ledAssociations()->where('status', AssociationStatus::EnAttente)->first(),
            'colors' => self::COLORS,
        ]);
    }

    /**
     * Demande de création envoyée à la mairie.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:associations,name'],
            'description' => ['required', 'string', 'min:20', 'max:1000'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'motivation' => ['required', 'string', 'min:10', 'max:1000'],
            'color' => ['required', Rule::in(self::COLORS)],
        ], [
            'name.unique' => 'Une association porte déjà ce nom.',
            'description.min' => 'Décrivez votre association en quelques phrases (20 caractères minimum).',
            'motivation.required' => 'Dites à la mairie pourquoi vous voulez créer ce groupe.',
        ]);

        $association = Association::create([
            ...$data,
            'leader_id' => $request->user()->id,
            'status' => AssociationStatus::EnAttente,
        ]);
        $association->memberships()->create(['user_id' => $request->user()->id, 'status' => MembershipStatus::Membre]);

        return redirect()->route('profile')->with('success', 'Demande envoyée à la mairie. Vous serez chef du groupe dès qu\'elle sera acceptée.');
    }

    public function show(Request $request, Association $association): View
    {
        abort_unless($association->status === AssociationStatus::Active || $association->isLedBy($request->user()), 404);

        return view('associations.show', [
            'association' => $association->load(['village', 'leader'])->loadCount('members'),
            'posts' => $association->posts()->get(),
            'membership' => $association->membershipOf($request->user()),
            'isLeader' => $association->isLedBy($request->user()),
        ]);
    }

    public function join(Request $request, Association $association): RedirectResponse
    {
        abort_unless($association->status === AssociationStatus::Active, 404);

        $membership = $association->memberships()->firstOrNew(['user_id' => $request->user()->id]);
        if ($membership->status !== MembershipStatus::Membre) {
            $membership->status = MembershipStatus::EnAttente;
            $membership->save();
        }

        return back()->with('success', 'Demande envoyée ! Le chef du groupe va vous répondre.');
    }

    public function leave(Request $request, Association $association): RedirectResponse
    {
        if ($association->isLedBy($request->user())) {
            return back()->withErrors(['association' => 'Le chef du groupe ne peut pas le quitter.']);
        }

        $association->memberships()->where('user_id', $request->user()->id)->delete();

        return back()->with('success', 'Vous avez quitté '.$association->name.'.');
    }
}
