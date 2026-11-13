@extends('layouts.guest')

@section('content')
<div class="welcome">
    <div style="max-width: 460px; margin: 0 auto; width: 100%">
        <span class="brand-mark mb-4" style="width:56px;height:56px;border-radius:16px;font-size:1.6rem;background:#fff;color:#0f766e"><i class="bi bi-trash3-fill"></i></span>
        <h1>Une ville propre commence par une communauté engagée.</h1>
        <p class="mt-3 mb-5" style="color:#bfe0db">Signalez un dépôt sauvage en une photo, suivez son nettoyage et sachez quand passe le camion dans votre village.</p>
        <div class="d-grid gap-2">
            <a href="{{ route('register') }}" class="btn btn-sun btn-lg">Créer un compte</a>
            <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">J'ai déjà un compte</a>
        </div>
    </div>
</div>
@endsection
