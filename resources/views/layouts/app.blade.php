<!doctype html>
<html lang="fr">
<head>
    @include('partials.head')
</head>
<body class="d-flex flex-column min-vh-100">
@php($u = auth()->user())
<nav class="navbar navbar-expand-xl cc-navbar sticky-top">
    <div class="container">
        <a class="brand" href="{{ route('home') }}">
            <span class="brand-mark"><i class="bi bi-trash3-fill"></i></span>
            <span>Clean<b>Comores</b></span>
        </a>

        <div class="d-flex align-items-center gap-1 order-xl-last ms-auto ms-xl-3">
            @auth
                @include('partials.notifications')
                @include('partials.user-menu')
            @else
                <a href="{{ route('login') }}" class="btn btn-soft btn-sm d-none d-sm-inline-block">Connexion</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm ms-1">Créer un compte</a>
            @endauth
            <button class="navbar-toggler border-0 ms-1" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-xl-4 me-auto gap-xl-1 mt-2 mt-xl-0">
                @if ($u?->isCitizen())
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('citizen.dashboard')) active @endif" href="{{ route('citizen.dashboard') }}">Mon espace</a></li>
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('citizen.reports.index', 'citizen.reports.show')) active @endif" href="{{ route('citizen.reports.index') }}">Mes signalements</a></li>
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('citizen.pickups.*')) active @endif" href="{{ route('citizen.pickups.index') }}">Collecte sur demande</a></li>
                @elseif ($u?->isAgent())
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('agent.dashboard')) active @endif" href="{{ route('agent.dashboard') }}">Ma tournée</a></li>
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('agent.planning')) active @endif" href="{{ route('agent.planning') }}">Planning de la semaine</a></li>
                @elseif ($u?->isStaff())
                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-1"></i>Espace de gestion</a></li>
                @endif
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('calendar')) active @endif" href="{{ route('calendar') }}">Calendrier de collecte</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('map')) active @endif" href="{{ route('map') }}">Carte de propreté</a></li>
                @guest
                    <li class="nav-item d-sm-none"><a class="nav-link" href="{{ route('login') }}">Connexion</a></li>
                @endguest
            </ul>
            @if (! $u || $u->isCitizen())
                <a href="{{ $u ? route('citizen.reports.create') : route('login') }}" class="btn btn-sun btn-sm d-none d-xl-inline-flex align-items-center gap-1">
                    <i class="bi bi-camera-fill"></i> Signaler des déchets
                </a>
            @endif
        </div>
    </div>
</nav>

<main class="flex-grow-1">
    @hasSection('fullwidth')
        <div class="container pt-3">@include('partials.flash')</div>
        @yield('fullwidth')
    @else
        <div class="container pb-5">
            <div class="pt-3">@include('partials.flash')</div>
            @yield('content')
        </div>
    @endif
</main>

@if ($u?->isCitizen())
    {{-- Bouton d'action rapide sur mobile --}}
    <a href="{{ route('citizen.reports.create') }}" class="btn btn-sun rounded-pill shadow-lg position-fixed d-xl-none d-flex align-items-center gap-2 px-3 py-2" style="right:16px;bottom:16px;z-index:1030">
        <i class="bi bi-camera-fill"></i> Signaler
    </a>
@endif

<footer class="footer py-4 mt-auto">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2 small text-muted">
        <span><strong class="text-body">CleanComores</strong> — « Une ville propre commence par une communauté engagée. »</span>
        <span>Commune pilote : Moroni · Ngazidja · Prototype hackathon</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
