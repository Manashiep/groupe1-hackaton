@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
<div class="row justify-content-center py-4 py-lg-5 g-4">
    <div class="col-md-7 col-lg-5">
        <div class="card">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h3">Connexion</h1>
                <p class="text-muted">Accédez à votre espace citoyen, agent ou responsable.</p>
                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse e-mail</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="email">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label small" for="remember">Rester connecté</label>
                    </div>
                    <button class="btn btn-primary w-100 btn-lg">Se connecter</button>
                </form>
                <p class="text-center small text-muted mt-4 mb-0">Pas encore de compte ? <a href="{{ route('register') }}" class="fw-semibold">Inscrivez-vous gratuitement</a></p>
            </div>
        </div>
    </div>

    @if (app()->environment('local'))
        <div class="col-md-7 col-lg-4">
            <div class="card demo-accounts" style="background: var(--cc-sun-soft); border-color: #f3e2a6">
                <div class="card-body p-4">
                    <div class="small-caps" style="color:#8a6a00">Démo hackathon</div>
                    <h2 class="h5 mt-1">Comptes de démonstration</h2>
                    <p class="small text-muted">Cliquez sur un profil pour pré-remplir le formulaire. Ce bloc n'apparaît qu'en environnement local.</p>
                    <div class="d-grid gap-2">
                        @foreach ([
                            ['citoyen@cleancomores.km', 'Citoyen', 'Nadia · Badjanani', 'bi-person'],
                            ['agent.centre@cleancomores.km', 'Agent de collecte', 'Saïd · Équipe Centre', 'bi-truck'],
                            ['responsable@cleancomores.km', 'Responsable municipal', 'Fatima · Mairie', 'bi-building'],
                            ['admin@cleancomores.km', 'Administrateur', 'Gestion des comptes', 'bi-shield-lock'],
                        ] as [$email, $role, $who, $icon])
                            <button type="button" class="btn btn-soft d-flex align-items-center gap-3 demo-fill" data-email="{{ $email }}">
                                <i class="bi {{ $icon }} fs-5 text-primary"></i>
                                <span class="lh-sm"><strong class="d-block small">{{ $role }}</strong><span class="text-muted" style="font-size:.78rem">{{ $who }}</span></span>
                            </button>
                        @endforeach
                    </div>
                    <p class="small text-muted mt-3 mb-0">Mot de passe commun : <code>password</code></p>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.demo-fill').forEach((btn) => btn.addEventListener('click', () => {
    document.getElementById('email').value = btn.dataset.email;
    document.getElementById('password').value = 'password';
    document.getElementById('password').focus();
}));
</script>
@endpush
