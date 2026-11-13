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
            ['agent.route', 'agent.route', 'bi-signpost-split', 'Tournée'],
            ['agent.planning', 'agent.planning', 'bi-calendar3', 'Planning'],
            ['map', 'map', 'bi-map', 'Carte'],
            ['reports.index', 'reports.*', 'bi-flag', 'Signalements'],
            ['pickups.index', 'pickups.*', 'bi-box-seam', 'Demandes'],
        ]
        : [
            ['map', 'map', 'bi-map', 'Carte'],
            ['reports.index', 'reports.*', 'bi-flag', 'Signalements'],
            ['trucks.index', 'trucks.*', 'bi-truck', 'Camion'],
            ['pickups.index', 'pickups.*', 'bi-box-seam', 'Demandes'],
        ];
@endphp

<header class="topbar">
    <a class="brand" href="{{ $user->homeUrl() }}">
        <span class="brand-mark"><i class="bi bi-trash3-fill"></i></span>
        <span>Clean<b>Comores</b></span>
    </a>
    <nav class="toptabs" aria-label="Navigation principale">
        @foreach ($tabs as [$route, $pattern, $icon, $label])
            <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</a>
        @endforeach
    </nav>
    <div class="dropdown ms-auto">
        <button class="avatar" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mon compte">{{ $user->initials() }}</button>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4">
            <li class="px-3 py-2"><strong class="small d-block">{{ $user->name }}</strong><span class="text-muted small">{{ $user->role->getLabel() }}</span></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Se déconnecter</button>
                </form>
            </li>
        </ul>
    </div>
</header>

<main>
    @yield('content')
</main>

<nav class="tabbar" aria-label="Navigation principale">
    @foreach ($tabs as [$route, $pattern, $icon, $label])
        <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}"><i class="bi {{ $icon }}"></i>{{ $label }}</a>
    @endforeach
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
