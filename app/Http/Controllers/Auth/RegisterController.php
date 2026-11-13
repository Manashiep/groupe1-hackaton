<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Quartier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'quartiers' => Quartier::orderBy('ville')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'quartier_id' => ['required', 'exists:quartiers,id'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // L'inscription publique crée toujours un compte citoyen.
        $user = User::create([...$data, 'role' => Role::Citoyen]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('citizen.dashboard')
            ->with('success', 'Votre compte est créé. Bienvenue sur CleanComores !');
    }
}
