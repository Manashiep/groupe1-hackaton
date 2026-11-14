@extends('layouts.guest')

@section('content')
<div class="welcome">
    <div class="welcome-inner">
        <div>
            <a class="brand mb-5 d-inline-flex" href="{{ route('home') }}"><span class="brand-mark">@include('partials.leaf')</span><span>Eco<b>Com</b></span></a>
            <h1>Nos villages propres, <em>ensemble.</em></h1>
            <p class="lead fs-5 mt-3 mb-4">Signalez un dépôt sauvage, prenez-le en charge avec vos voisins ou une association, et sachez quand passe le camion.</p>
            <div class="d-grid d-sm-flex gap-2">
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4">Créer mon compte</a>
                <a href="{{ route('login') }}" class="btn btn-ghost btn-lg px-4">J'ai déjà un compte</a>
            </div>
        </div>

        <div class="preview-stage" aria-hidden="true"><div class="preview">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Près de chez vous</strong>
                <span class="pill pill-info"><i class="bi bi-geo-alt-fill"></i> Badjanani</span>
            </div>
            <div class="row-item">
                <span class="thumb ico-red"><i class="bi bi-trash3-fill"></i></span>
                <div class="flex-grow-1"><div class="row-title">Tas d'ordures derrière l'école</div><span class="pill st-nouveau mt-1"><span class="dot"></span>À nettoyer</span></div>
            </div>
            <div class="row-item">
                <span class="thumb ico-amber"><i class="bi bi-people-fill"></i></span>
                <div class="flex-grow-1"><div class="row-title">Ravine de Hadouja</div><div class="row-sub">Moroni Propre · samedi 8h00</div></div>
            </div>
            <div class="row-item">
                <span class="thumb"><i class="bi bi-truck"></i></span>
                <div class="flex-grow-1"><div class="row-title">Camion 1</div><div class="row-sub">Demain · 06:00 – 07:30</div></div>
            </div>
        </div></div>
    </div>
</div>
@endsection
