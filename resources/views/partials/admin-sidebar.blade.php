@php
    $badges = [
        'reports' => \App\Models\Report::where('status', \App\Enums\ReportStatus::Nouveau)->count(),
        'pickups' => \App\Models\PickupRequest::where('status', \App\Enums\PickupStatus::EnAttente)->count(),
    ];
    $nav = [
        'Pilotage' => [
            ['admin.dashboard', 'admin.dashboard', 'bi-speedometer2', 'Tableau de bord', null],
        ],
        'Terrain' => [
            ['admin.reports.index', 'admin.reports.*', 'bi-geo-alt', 'Signalements', $badges['reports']],
            ['admin.interventions.index', 'admin.interventions.*', 'bi-clipboard-check', 'Interventions', null],
            ['admin.collections.index', 'admin.collections.*', 'bi-truck', 'Collectes du jour', null],
            ['admin.routes.index', 'admin.routes.*', 'bi-signpost-split', 'Itinéraires', null],
            ['admin.pickups.index', 'admin.pickups.*', 'bi-box-seam', 'Demandes de collecte', $badges['pickups']],
        ],
        'Organisation' => [
            ['admin.schedules.index', 'admin.schedules.*', 'bi-calendar-week', 'Calendrier de collecte', null],
            ['admin.quartiers.index', 'admin.quartiers.*', 'bi-map', 'Quartiers & zones', null],
            ['admin.teams.index', 'admin.teams.*', 'bi-people', 'Équipes & véhicules', null],
        ],
    ];
    if (auth()->user()->isAdmin()) {
        $nav['Administration'] = [['admin.users.index', 'admin.users.*', 'bi-person-gear', 'Comptes utilisateurs', null]];
    }
@endphp
<div class="px-3 py-3 border-bottom border-white border-opacity-10">
    <a class="brand" href="{{ route('admin.dashboard') }}">
        <span class="brand-mark"><i class="bi bi-trash3-fill"></i></span>
        <span>Clean<b>Comores</b></span>
    </a>
    <div class="small mt-2" style="color:#7d8f99">Service propreté · Moroni</div>
</div>
<nav class="flex-grow-1 pb-3">
    @foreach ($nav as $section => $items)
        <div class="nav-section">{{ $section }}</div>
        @foreach ($items as [$route, $pattern, $icon, $label, $badge])
            <a href="{{ route($route) }}" class="nav-link @if(request()->routeIs($pattern)) active @endif">
                <i class="bi {{ $icon }}"></i> {{ $label }}
                @if ($badge)<span class="badge rounded-pill text-bg-warning">{{ $badge }}</span>@endif
            </a>
        @endforeach
    @endforeach
</nav>
<div class="px-3 py-3 border-top border-white border-opacity-10 small">
    <a href="{{ route('home') }}" class="text-decoration-none" style="color:#9fb0b9"><i class="bi bi-globe2 me-1"></i> Voir le site public</a>
</div>
