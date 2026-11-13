@extends('layouts.guest')

@section('title', 'Inscription')

@section('content')
<div class="auth-wrap">
    <a class="brand mb-4" href="{{ route('home') }}"><span class="brand-mark"><i class="bi bi-trash3-fill"></i></span><span>Clean<b>Comores</b></span></a>
    <h1 class="h3">Créer un compte</h1>
    <p class="text-muted mb-4">Gratuit, en moins d'une minute.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Nom</label>
            <input id="name" name="name" value="{{ old('name') }}" class="form-control" required autocomplete="name">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autocomplete="email">
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Téléphone <span class="text-muted fw-normal">(facultatif)</span></label>
            <input id="phone" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="+269 …" autocomplete="tel">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe <span class="text-muted fw-normal">(8 caractères minimum)</span></label>
            <input id="password" type="password" name="password" class="form-control" required autocomplete="new-password">
        </div>
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
        </div>
        <button class="btn btn-primary btn-lg w-100">Créer mon compte</button>
    </form>
    <p class="text-center small text-muted mt-4">Déjà inscrit ? <a href="{{ route('login') }}" class="fw-bold">Se connecter</a></p>
</div>
@endsection
