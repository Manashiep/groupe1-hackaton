@extends('layouts.guest')

@section('title', 'Connexion')

@section('content')
<div class="auth-wrap">
    <a class="brand mb-4" href="{{ route('home') }}"><span class="brand-mark"><i class="bi bi-trash3-fill"></i></span><span>Clean<b>Comores</b></span></a>
    <h1 class="h3">Connexion</h1>
    <p class="text-muted mb-4">Content de vous revoir.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus autocomplete="email">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input id="password" type="password" name="password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
            <label class="form-check-label small" for="remember">Rester connecté</label>
        </div>
        <button class="btn btn-primary btn-lg w-100">Se connecter</button>
    </form>
    <p class="text-center small text-muted mt-4">Pas encore de compte ? <a href="{{ route('register') }}" class="fw-bold">Inscription</a></p>

    @if (app()->environment('local'))
        <div class="mt-4 p-3 rounded-4" style="background:#fff5d6">
            <div class="small fw-bold mb-2">Comptes de démo <span class="fw-normal text-muted">(mot de passe : password)</span></div>
            <div class="d-flex flex-wrap gap-2">
                @foreach (['citoyen@cleancomores.km' => 'Citoyen', 'agent@cleancomores.km' => 'Agent', 'responsable@cleancomores.km' => 'Responsable', 'admin@cleancomores.km' => 'Admin'] as $email => $label)
                    <button type="button" class="btn btn-light btn-sm demo-fill" data-email="{{ $email }}">{{ $label }}</button>
                @endforeach
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
}));
</script>
@endpush
