<!doctype html>
<html lang="fr">
<head>
    @include('partials.head')
</head>
<body>
@php
    $user = auth()->user();
    $tabs = $user->isAgent()
        ? [
            ['agent.route', 'agent.*', 'bi-truck', 'Tournée'],
            ['map', 'map', 'bi-geo-alt', 'Carte'],
            null,
            ['associations.index', 'associations.*', 'bi-people', 'Assos'],
            ['profile', 'profile', 'bi-person', 'Moi'],
        ]
        : [
            ['map', 'map', 'bi-geo-alt', 'Carte'],
            ['associations.index', 'associations.*', 'bi-people', 'Assos'],
            null,
            ['trucks.index', 'trucks.*', 'bi-calendar-week', 'Camion'],
            ['profile', 'profile', 'bi-person', 'Moi'],
        ];
    $led = $user->activeLedAssociation();
@endphp

<header class="topbar">
    <a class="brand" href="{{ $user->homeUrl() }}">
        <span class="brand-mark">@include('partials.leaf')</span>
        <span>Clean<b>Comores</b></span>
    </a>
    <nav class="toptabs" aria-label="Navigation principale">
        @foreach (array_filter($tabs) as [$route, $pattern, $icon, $label])
            <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>
    <button type="button" class="btn btn-primary btn-sm ms-auto d-none desktop-plus align-items-center gap-1" data-bs-toggle="offcanvas" data-bs-target="#action-sheet">
        <i class="bi bi-plus-lg"></i> Agir
    </button>
    <a href="{{ route('profile') }}" class="avatar ms-auto ms-lg-2 text-decoration-none" aria-label="Mon profil">{{ $user->initials() }}</a>
</header>

<main>
    @yield('content')
</main>

<nav class="tabbar" aria-label="Navigation principale">
    @foreach ($tabs as $tab)
        @if ($tab === null)
            <div class="plus-slot">
                <button type="button" class="plus" data-bs-toggle="offcanvas" data-bs-target="#action-sheet" aria-label="Signaler ou demander">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
        @else
            <a href="{{ route($tab[0]) }}" class="{{ request()->routeIs($tab[1]) ? 'active' : '' }}" @if(request()->routeIs($tab[1])) aria-current="page" @endif>
                <i class="bi {{ $tab[2] }}{{ request()->routeIs($tab[1]) ? '-fill' : '' }}"></i>{{ $tab[3] }}
            </a>
        @endif
    @endforeach
</nav>

<div class="offcanvas offcanvas-bottom action-sheet" tabindex="-1" id="action-sheet" aria-labelledby="action-sheet-title">
    <div class="sheet-handle"></div>
    <div class="offcanvas-body pt-2 mx-auto w-100" style="max-width: 560px">
        <h2 class="h5 px-2 mb-2" id="action-sheet-title">Que voulez-vous faire ?</h2>
        <a href="{{ route('reports.create') }}" class="sheet-option">
            <span class="ico ico-red"><i class="bi bi-camera-fill"></i></span>
            <span><strong class="d-block">Signaler un dépôt sauvage</strong><span class="text-muted small">Une photo, l'endroit, c'est envoyé.</span></span>
        </a>
        <a href="{{ route('pickups.create') }}" class="sheet-option">
            <span class="ico ico-leaf"><i class="bi bi-box-seam-fill"></i></span>
            <span><strong class="d-block">Demander une collecte</strong><span class="text-muted small">Beaucoup de déchets ou des encombrants ? Un volontaire passe.</span></span>
        </a>
        @if ($led)
            <a href="{{ route('associations.manage.show', $led) }}#publier" class="sheet-option">
                <span class="ico ico-amber"><i class="bi bi-megaphone-fill"></i></span>
                <span><strong class="d-block">Publier pour {{ $led->name }}</strong><span class="text-muted small">Une annonce ou des photos de votre dernier nettoyage.</span></span>
            </a>
        @endif
        @if ($user->isAgent())
            <a href="{{ route('agent.route') }}#prevenir" class="sheet-option">
                <span class="ico ico-blue"><i class="bi bi-broadcast"></i></span>
                <span><strong class="d-block">Prévenir les habitants</strong><span class="text-muted small">Tournée pas terminée, retard, passage annulé.</span></span>
            </a>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
