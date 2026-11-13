@extends('layouts.app')

@section('title', 'Ma tournée')

@include('partials.leaflet')

@section('content')
<div class="hero">
    <div class="inner">
        @if (! $truck)
            <h1>Ma tournée</h1>
        @else
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h1>{{ $truck->name }}</h1>
                    <p class="mb-0">{{ ucfirst(today()->translatedFormat('l j F')) }}</p>
                </div>
                @include('agent._tabs')
            </div>
            @forelse ($slots as $slot)
                <div class="slot">
                    <span class="time">{{ $slot->timeRange() }}</span>
                    <span class="fw-bold">{{ $slot->village->name }}</span>
                </div>
            @empty
                <div class="slot"><i class="bi bi-cup-hot"></i> Pas de ramassage prévu aujourd'hui.</div>
            @endforelse
        @endif
    </div>
</div>

<div class="sheet">
    <div class="inner">
        @include('partials.flash')

        @if (! $truck)
            <div class="empty"><span class="icon-chip"><i class="bi bi-truck"></i></span><strong>Aucun camion ne vous est affecté</strong>Demandez au responsable de la mairie de vous rattacher à un camion.</div>
        @else
            @foreach ($notices as $notice)
                <div class="callout mb-3"><i class="bi {{ $notice->kind->icon() }}"></i><div><strong>Alerte envoyée · {{ $notice->kind->label() }}</strong>@if ($notice->message)<div>{{ $notice->message }}</div>@endif<div class="small" style="opacity:.75">{{ $notice->created_at->diffForHumans() }}</div></div></div>
            @endforeach

            <div class="panel mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">Points de collecte</h2>
                    <span class="fw-bold">{{ count($visited) }} / {{ $stops->count() }}</span>
                </div>
                <div class="progress mb-3" role="progressbar" aria-label="Avancement de la tournée" aria-valuenow="{{ count($visited) }}" aria-valuemin="0" aria-valuemax="{{ $stops->count() }}">
                    <div class="progress-bar" style="width: {{ $stops->count() ? count($visited) / $stops->count() * 100 : 0 }}%"></div>
                </div>
                <div id="map" class="map mb-2"></div>
                @forelse ($stops as $stop)
                    @php($done = in_array($stop->id, $visited, true))
                    <div class="stop {{ $done ? 'done' : '' }}">
                        <span class="n" style="background: {{ $truck->color }}">{{ $stop->position }}</span>
                        <span class="name flex-grow-1">{{ $stop->displayName() }}</span>
                        <form method="POST" action="{{ route('agent.stops.toggle', $stop) }}">
                            @csrf
                            @if ($done)
                                <button class="btn btn-ghost btn-sm" aria-label="Annuler"><i class="bi bi-arrow-counterclockwise"></i></button>
                            @else
                                <button class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Fait</button>
                            @endif
                        </form>
                    </div>
                @empty
                    <p class="text-muted mb-0">Le responsable n'a pas encore tracé votre itinéraire.</p>
                @endforelse
            </div>

            <div class="section-title" id="prevenir"><h2>Prévenir les habitants</h2></div>
            <form method="POST" action="{{ route('agent.notices.store') }}" class="panel">
                @csrf
                <p class="text-muted mb-3">L'alerte s'affiche pendant 24 h sur la page « Camion » de {{ $truck->villages->pluck('name')->join(', ', ' et ') }}.</p>
                <div class="d-grid gap-2 mb-3">
                    @foreach ($kinds as $kind)
                        <div class="kind-option">
                            <input type="radio" name="kind" value="{{ $kind->value }}" id="kind-{{ $kind->value }}" @checked($loop->first)>
                            <label for="kind-{{ $kind->value }}"><i class="bi {{ $kind->icon() }}"></i> {{ $kind->label() }}</label>
                        </div>
                    @endforeach
                </div>
                <input name="message" maxlength="300" class="form-control mb-3" placeholder="Ex. : Ntsoudjini pas fait, on repasse demain à 7h">
                <button class="btn btn-primary w-100"><i class="bi bi-broadcast me-1"></i> Envoyer l'alerte</button>
            </form>
        @endif
    </div>
</div>
@endsection

@if ($truck)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const stops = @js($stops->map(fn ($s) => ['n' => $s->position, 'lat' => $s->latitude, 'lng' => $s->longitude, 'name' => $s->displayName(), 'done' => in_array($s->id, $visited, true)])->values());
    const color = @js($truck->color);
    const map = CC.baseMap('map');
    if (stops.length > 1) {
        L.polyline(stops.map((s) => [s.lat, s.lng]), { color, weight: 4, opacity: .7, dashArray: '8 8' }).addTo(map);
    }
    stops.forEach((s) => L.marker([s.lat, s.lng], { icon: CC.numberPin(s.n, s.done ? '#1f8a4c' : color) }).addTo(map).bindPopup(CC.escape(s.name)));
    CC.fit(map, stops.map((s) => [s.lat, s.lng]), 16);
});
</script>
@endpush
@endif
