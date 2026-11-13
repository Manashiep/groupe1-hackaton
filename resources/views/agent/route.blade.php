@extends('layouts.app')

@section('title', 'Ma tournée')

@include('partials.leaflet')

@section('content')
<div class="page">
    @if (! $truck)
        <h1>Ma tournée</h1>
        <div class="empty"><i class="bi bi-truck"></i>Vous n'êtes rattaché à aucun camion. Demandez au responsable de vous en affecter un.</div>
    @else
        <div class="small text-muted fw-bold">{{ $truck->name }} · {{ $truck->village?->name }}</div>
        <h1>Tournée du jour</h1>
        <p class="text-muted mb-3">
            {{ ucfirst(today()->translatedFormat('l j F')) }} ·
            {{ $schedule ? $schedule->timeRange() : 'pas de passage prévu aujourd\'hui' }}
        </p>

        @include('partials.flash')

        <div id="map" class="map mb-3" style="height: 320px"></div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h2 class="h6 mb-0">Points de collecte</h2>
                    <span class="small fw-bold">{{ count($visited) }} / {{ $stops->count() }}</span>
                </div>
                @forelse ($stops as $stop)
                    @php($done = in_array($stop->id, $visited, true))
                    <div class="stop {{ $done ? 'done' : '' }}">
                        <span class="n">{{ $stop->position }}</span>
                        <span class="name flex-grow-1">{{ $stop->displayName() }}</span>
                        <form method="POST" action="{{ route('agent.stops.toggle', $stop) }}">
                            @csrf
                            <button class="btn btn-sm {{ $done ? 'btn-light' : 'btn-primary' }}" aria-label="{{ $done ? 'Annuler' : 'Marquer comme collecté' }}">
                                {!! $done ? '<i class="bi bi-arrow-counterclockwise"></i>' : '<i class="bi bi-check-lg"></i> Fait' !!}
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-muted small mb-0 mt-2">Le responsable n'a pas encore tracé votre itinéraire.</p>
                @endforelse
            </div>
        </div>

        @if ($reports->isNotEmpty() || $pickups->isNotEmpty())
            <h2 class="h6 mt-4 mb-2">Missions du jour</h2>
            @foreach ($reports as $report)
                <div class="item">
                    <div class="thumb">@if ($report->photoUrl())<img src="{{ $report->photoUrl() }}" alt="">@else<i class="bi bi-trash3"></i>@endif</div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">Nettoyer un dépôt sauvage</div>
                        <div class="small text-muted">{{ $report->cleanup_at->isToday() ? 'Aujourd\'hui '.$report->cleanup_at->format('H\hi') : 'En retard · prévu '.$report->cleanup_at->translatedFormat('j M') }}</div>
                        @if ($report->description)<div class="small">{{ $report->description }}</div>@endif
                        <div class="d-flex gap-2 mt-2">
                            <a class="btn btn-light btn-sm" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination={{ $report->latitude }},{{ $report->longitude }}"><i class="bi bi-sign-turn-right"></i> Y aller</a>
                            @if ($report->status === \App\Enums\ReportStatus::PrisEnCharge)
                                <form method="POST" action="{{ route('agent.reports.start', $report) }}">@csrf<button class="btn btn-outline-primary btn-sm">Commencer</button></form>
                            @endif
                            <form method="POST" action="{{ route('agent.reports.finish', $report) }}">@csrf<button class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Nettoyé</button></form>
                        </div>
                    </div>
                </div>
            @endforeach
            @foreach ($pickups as $pickup)
                <div class="item">
                    <div class="thumb"><i class="bi bi-box-seam"></i></div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">Collecte sur demande</div>
                        <div class="small text-muted">{{ $pickup->appointment_at->isToday() ? 'Aujourd\'hui '.$pickup->appointment_at->format('H\hi') : 'En retard · prévu '.$pickup->appointment_at->translatedFormat('j M') }}</div>
                        <div class="small">{{ $pickup->description }}</div>
                        <div class="d-flex gap-2 mt-2">
                            <a class="btn btn-light btn-sm" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination={{ $pickup->latitude }},{{ $pickup->longitude }}"><i class="bi bi-sign-turn-right"></i> Y aller</a>
                            <form method="POST" action="{{ route('agent.pickups.finish', $pickup) }}">@csrf<button class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Effectuée</button></form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    @endif
</div>
@endsection

@if ($truck)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const stops = @js($stops->map(fn ($s) => ['n' => $s->position, 'lat' => $s->latitude, 'lng' => $s->longitude, 'name' => $s->displayName(), 'done' => in_array($s->id, $visited, true)])->values());
    const missions = @js($reports->map(fn ($r) => [$r->latitude, $r->longitude])->concat($pickups->map(fn ($p) => [$p->latitude, $p->longitude]))->values());
    const color = @js($truck->color);
    const map = CC.baseMap('map');

    if (stops.length > 1) {
        L.polyline(stops.map((s) => [s.lat, s.lng]), { color, weight: 4, opacity: .7, dashArray: '8 8' }).addTo(map);
    }
    stops.forEach((s) => L.marker([s.lat, s.lng], { icon: CC.numberPin(s.n, s.done ? '#16a34a' : color) }).addTo(map).bindPopup(CC.escape(s.name)));
    missions.forEach((m) => L.marker(m, { icon: CC.pin('#d97706') }).addTo(map).bindPopup('Mission du jour'));

    CC.fit(map, stops.map((s) => [s.lat, s.lng]).concat(missions), 16);
});
</script>
@endpush
@endif
