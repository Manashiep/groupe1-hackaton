<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Quartier;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Gestion des comptes (réservée aux administrateurs).
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['role', 'q']);

        return view('admin.users.index', [
            'users' => User::with(['quartier', 'team'])
                ->when($filters['role'] ?? null, fn ($q, $r) => $q->where('role', $r))
                ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")))
                ->orderByRaw("FIELD(role, 'admin', 'responsable', 'agent', 'citoyen')")
                ->orderBy('name')
                ->paginate(20)->withQueryString(),
            'filters' => $filters,
            'roles' => Role::cases(),
            'counts' => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['role' => Role::Agent, 'is_active' => true]), ...$this->formData()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = User::create($this->validated($request));

        return redirect()->route('admin.users.index')->with('success', "Compte de {$user->name} créé ({$user->role->label()}).");
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user, ...$this->formData()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && ($data['role'] !== Role::Admin->value || ! ($data['is_active'] ?? false))) {
            return back()->with('error', 'Vous ne pouvez pas retirer vos propres droits d\'administrateur.');
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', "Compte de {$user->name} mis à jour.");
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'roles' => Role::cases(),
            'teams' => Team::orderBy('name')->get(),
            'quartiers' => Quartier::orderBy('name')->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::enum(Role::class)],
            'team_id' => ['nullable', 'exists:teams,id'],
            'quartier_id' => ['nullable', 'exists:quartiers,id'],
            'password' => [$user ? 'nullable' : 'required', Password::min(8)],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        // Une équipe n'a de sens que pour un agent.
        if ($data['role'] !== Role::Agent->value) {
            $data['team_id'] = null;
        }

        return $data;
    }
}
