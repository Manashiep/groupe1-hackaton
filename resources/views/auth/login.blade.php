@extends('layouts.guest')

@section('title', 'Connexion')

@section('content')
<div class="auth">
    <div class="auth-top">
        <div class="inner">
            <a class="brand" href="{{ route('home') }}"><span class="brand-mark">@include('partials.leaf')</span><span>Clean<b>Comores</b></span></a>
            <h1>Bon retour !</h1>
            <p class="mb-0">Connectez-vous pour voir ce qui se passe près de chez vous.</p>
        </div>
    </div>

    <div class="auth-card">
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
                <label class="form-check-label" for="remember">Rester connecté</label>
            </div>
            <button class="btn btn-primary btn-lg w-100">Se connecter</button>
        </form>
        <p class="text-center text-muted mt-4 mb-0">Pas encore de compte ? <a href="{{ route('register') }}" class="fw-bold">Inscription</a></p>

        @if (app()->environment('local'))
            <div class="callout mt-4">
                <i class="bi bi-lightning-charge-fill"></i>
                <div class="w-100">
                    <div class="fw-bold mb-2">Comptes de démo <span class="fw-normal">· mot de passe « password »</span></div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (['citoyen@cleancomores.km' => 'Citoyen', 'asso@cleancomores.km' => 'Chef d\'asso', 'agent@cleancomores.km' => 'Agent', 'responsable@cleancomores.km' => 'Mairie'] as $email => $label)
                            <button type="button" class="btn btn-sm bg-white demo-fill" data-email="{{ $email }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
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
