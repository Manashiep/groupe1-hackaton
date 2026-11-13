@extends('layouts.admin')

@section('title', 'Tableau de bord')

@include('partials.leaflet')

@push('head')
<style>
    /* Rôles de couleur des graphiques (palette de référence validée : slots 1 et 2, rampe bleue). */
    .viz-root {
        --surface-1: #ffffff;
        --text-primary: #13212b;
        --text-secondary: #52514e;
        --text-muted: #898781;
        --grid: #e1e0d9;
        --baseline: #c3c2b7;
        --series-1: #2a78d6;
        --series-2: #eb6834;
    }
    .legend-key { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 6px; vertical-align: -1px; }
</style>
@endpush

@section('content')
<div class="row g-3 mb-4">
    @php
        $tiles = [
            ['Signalements reçus', $kpis['reports30'], 'sur 30 jours'.($kpis['reportsDelta'] !== null ? ' · '.($kpis['reportsDelta'] > 0 ? '+' : '').$kpis['reportsDelta'].' % vs période précédente' : ''), 'bi-geo-alt', 'primary', route('admin.reports.index')],
            ['Problèmes en attente', $kpis['open'], $kpis['new'].' nouveau(x) à vérifier', 'bi-hourglass-split', 'warning', route('admin.reports.index', ['status' => 'ouverts'])],
            ['Collectes effectuées', $kpis['collectionsDone'], ($kpis['collectionRate'] !== null ? $kpis['collectionRate'].' % des passages prévus' : '—').' · '.$kpis['missed'].' manqué(s)', 'bi-truck', 'success', route('admin.collections.index')],
            ['Interventions réalisées', $kpis['interventionsDone'], 'sur 30 jours · '.$kpis['interventionsPlanned'].' en cours ou planifiée(s)', 'bi-clipboard-check', 'info', route('admin.interventions.index')],
            ['Demandes de collecte', $kpis['pickupsPending'], 'en attente de rendez-vous', 'bi-box-seam', 'secondary', route('admin.pickups.index')],
        ];
    @endphp
    @foreach ($tiles as [$label, $value, $foot, $icon, $color, $url])
        <div class="col-6 col-xl">
            <a href="{{ $url }}" class="card kpi text-decoration-none text-body">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="kpi-label">{{ $label }}</span>
                        <span class="kpi-icon bg-{{ $color }}-subtle text-{{ $color }}-emphasis"><i class="bi {{ $icon }}"></i></span>
                    </div>
                    <div class="kpi-value mt-1">{{ $value }}</div>
                    <div class="kpi-foot mt-1">{{ $foot }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card h-100 viz-root">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>Signalements reçus et résolus par semaine</span>
                <span class="small fw-normal" style="color: var(--text-secondary)">
                    <span class="legend-key" style="background: var(--series-1)"></span>Reçus
                    <span class="legend-key ms-3" style="background: var(--series-2)"></span>Résolus
                </span>
            </div>
            <div class="card-body">
                <div style="height: 260px"><canvas id="weeklyChart" role="img" aria-label="Signalements reçus et résolus sur les 8 dernières semaines"></canvas></div>
                <details class="mt-2 small">
                    <summary class="text-muted">Voir les données</summary>
                    <table class="table table-sm mt-2 mb-0 tabular">
                        <thead><tr><th>Semaine du</th><th class="text-end">Reçus</th><th class="text-end">Résolus</th></tr></thead>
                        <tbody>
                            @foreach ($weeks as $week)
                                <tr><td>{{ $week['label'] }}</td><td class="text-end">{{ $week['received'] }}</td><td class="text-end">{{ $week['resolved'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </details>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span>Collectes d'aujourd'hui</span>
                <a href="{{ route('admin.collections.index') }}" class="small fw-semibold text-decoration-none">Détail</a>
            </div>
            <div class="card-body">
                @forelse ($todayByTeam as $team)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-semibold"><span class="legend-key" style="background: {{ $team->color }}; border-radius:50%"></span>{{ $team->name }}</span>
                            <span class="text-muted tabular">{{ $team->today_done }}/{{ $team->today_total }} @if($team->today_running) · <span class="text-primary">1 en cours</span>@endif</span>
                        </div>
                        <div class="progress" style="height:8px"><div class="progress-bar" style="width: {{ $team->today_total ? $team->today_done / $team->today_total * 100 : 0 }}%"></div></div>
                    </div>
                @empty
                    <p class="text-muted small">Aucune équipe.</p>
                @endforelse
                <hr>
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.routes.index') }}" class="btn btn-soft btn-sm"><i class="bi bi-signpost-split me-1"></i> Voir les itinéraires</a>
                    <form method="POST" action="{{ route('admin.collections.reminders') }}" class="d-grid">
                        @csrf
                        <button class="btn btn-soft btn-sm"><i class="bi bi-bell me-1"></i> Envoyer les rappels de demain</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span>Problèmes ouverts sur le terrain</span>
                <a href="{{ route('map') }}" class="small fw-semibold text-decoration-none">Carte complète</a>
            </div>
            <div id="map" class="map rounded-0 border-0" style="height: 360px; border-radius: 0 0 var(--cc-radius) var(--cc-radius) !important"></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-exclamation-circle text-danger me-1"></i> À vérifier</span>
                <a href="{{ route('admin.reports.index', ['status' => 'nouveau']) }}" class="small fw-semibold text-decoration-none">Tout voir</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($toVerify as $report)
                    <a href="{{ route('admin.reports.show', $report) }}" class="list-group-item list-group-item-action d-flex gap-3 align-items-center">
                        <div class="report-thumb" style="width:48px;height:48px">
                            @if ($report->photoUrl())<img src="{{ $report->photoUrl() }}" alt="">@else<i class="bi {{ $report->category->icon() }}"></i>@endif
                        </div>
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="fw-semibold small">{{ $report->category->label() }} · {{ $report->quartier?->name }}</div>
                            <div class="small text-muted text-truncate">{{ $report->description }}</div>
                        </div>
                        <span class="small text-muted text-nowrap">{{ $report->created_at->diffForHumans(short: true) }}</span>
                    </a>
                @empty
                    <div class="empty"><i class="bi bi-check2-all"></i>Tous les signalements ont été vérifiés.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card h-100 viz-root">
            <div class="card-header">Zones les plus touchées <span class="text-muted fw-normal small">· signalements, 90 jours</span></div>
            <div class="card-body">
                <div style="height: 300px"><canvas id="zonesChart" role="img" aria-label="Nombre de signalements par quartier sur 90 jours"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Indice de propreté <span class="text-muted fw-normal small">· du plus fragile au plus propre</span></div>
            <div class="card-body pt-2" style="max-height: 352px; overflow-y: auto">
                @foreach ($ranking as $row)
                    <a href="{{ route('quartiers.show', $row['quartier']) }}" class="d-flex align-items-center gap-2 py-2 border-bottom text-decoration-none text-body small">
                        <span class="fw-semibold text-truncate flex-shrink-0" style="width: 90px">{{ $row['quartier']->name }}</span>
                        <x-score :data="$row" class="flex-grow-1" />
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-exclamation-diamond text-danger me-1"></i> Points noirs récurrents</div>
            <div class="list-group list-group-flush small">
                @forelse ($hotspots as $spot)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <strong>{{ $spot['quartier'] }}</strong>
                            <span class="d-block text-muted">{{ $spot['open'] }} encore ouvert(s) · dernier {{ $spot['last_at']->diffForHumans() }}</span>
                        </span>
                        <span class="badge text-bg-danger rounded-pill">{{ $spot['count'] }} dépôts</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted">Aucun point noir détecté.</div>
                @endforelse
            </div>
            <div class="card-body small text-muted pt-2">Même endroit (rayon 150 m), au moins 3 dépôts vérifiés en 90 jours : une action de fond (panneau, bac, sensibilisation) est à envisager.</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const css = getComputedStyle(document.querySelector('.viz-root'));
    const role = (name) => css.getPropertyValue(name).trim();

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.font.size = 12;
    Chart.defaults.color = role('--text-muted');

    const tooltip = {
        backgroundColor: '#13212b', titleColor: '#ffffff', bodyColor: '#e8eef1',
        padding: 10, cornerRadius: 8, boxPadding: 4, usePointStyle: true,
    };
    const axes = (indexAxis) => ({
        x: { grid: { display: indexAxis === 'y', color: role('--grid'), drawTicks: false }, border: { color: role('--baseline') }, ticks: { precision: 0, padding: 6 }, beginAtZero: true },
        y: { grid: { display: indexAxis !== 'y', color: role('--grid'), drawTicks: false }, border: { display: indexAxis === 'y', color: role('--baseline') }, ticks: { precision: 0, padding: 6, color: indexAxis === 'y' ? role('--text-secondary') : role('--text-muted') }, beginAtZero: true },
    });
    const bar = { borderRadius: 4, borderSkipped: 'start', borderWidth: 2, borderColor: role('--surface-1') };

    const weeks = @js($weeks);
    new Chart(document.getElementById('weeklyChart'), {
        type: 'bar',
        data: {
            labels: weeks.map((w) => w.label),
            datasets: [
                { label: 'Reçus', data: weeks.map((w) => w.received), backgroundColor: role('--series-1'), ...bar },
                { label: 'Résolus', data: weeks.map((w) => w.resolved), backgroundColor: role('--series-2'), ...bar },
            ],
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: { ...tooltip, callbacks: { title: (items) => 'Semaine du ' + items[0].label } } },
            scales: axes('x'),
            datasets: { bar: { categoryPercentage: 0.6, barPercentage: 0.9, maxBarThickness: 22 } },
        },
    });

    const zones = @js($zones->map(fn ($z) => ['name' => $z['quartier']->name, 'count' => $z['quartier']->reports_90]));
    new Chart(document.getElementById('zonesChart'), {
        type: 'bar',
        data: {
            labels: zones.map((z) => z.name),
            datasets: [{ label: 'Signalements (90 j)', data: zones.map((z) => z.count), backgroundColor: role('--series-1'), ...bar }],
        },
        options: {
            indexAxis: 'y',
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip },
            scales: axes('y'),
            datasets: { bar: { categoryPercentage: 0.75, barPercentage: 0.9, maxBarThickness: 18 } },
        },
    });

    const points = @js($mapPoints);
    const map = CC.baseMap('map', { zoom: 14 });
    points.forEach((p) => L.marker([p.lat, p.lng], { icon: CC.pin(p.color) }).addTo(map)
        .bindPopup(`<strong>${CC.escape(p.label)}</strong><br>${CC.escape(p.status)}<br><a href="${p.url}">Ouvrir</a>`));
    CC.fit(map, points.map((p) => [p.lat, p.lng]), 15);
});
</script>
@endpush
