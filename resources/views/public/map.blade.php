@extends('layouts.app')

@section('title', 'Carte de propreté')

@include('partials.leaflet')

@section('content')
<div class="page-head d-md-flex justify-content-between align-items-end">
    <div>
        <h1>Carte de propreté</h1>
        <p class="text-muted mb-0">Signalements des 90 derniers jours, indice de propreté des quartiers et points noirs récurrents.</p>
    </div>
    @if (! auth()->check() || auth()->user()->isCitizen())
        <a href="{{ auth()->check() ? route('citizen.reports.create') : route('login') }}" class="btn btn-sun mt-3 mt-md-0"><i class="bi bi-camera-fill me-1"></i> Signaler des déchets</a>
    @endif
</div>

<div class="row g-3">
    <div class="col-lg-3 order-2 order-lg-1">
        <div class="card mb-3">
            <div class="card-body">
                <div class="small-caps mb-2">Statut des signalements</div>
                @foreach ($statuses as $status)
                    @php($count = $reports->where('status', $status)->count())
                    <div class="form-check d-flex align-items-center gap-2 mb-1">
                        <input class="form-check-input status-filter mt-0" type="checkbox" value="{{ $status->value }}" id="f-{{ $status->value }}" checked>
                        <label class="form-check-label small d-flex align-items-center gap-2 flex-grow-1" for="f-{{ $status->value }}">
                            <i style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $status->hex() }}"></i>
                            {{ $status->label() }}
                            <span class="ms-auto text-muted tabular">{{ $count }}</span>
                        </label>
                    </div>
                @endforeach
                <hr>
                <div class="small-caps mb-2">Couches</div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="layer-zones" checked>
                    <label class="form-check-label small" for="layer-zones">Indice de propreté des quartiers</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="layer-hotspots" checked>
                    <label class="form-check-label small" for="layer-hotspots">Points noirs récurrents</label>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="bi bi-exclamation-diamond text-danger me-1"></i> Points noirs récurrents</div>
            <div class="list-group list-group-flush small">
                @forelse ($hotspots as $spot)
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center hotspot-link" data-lat="{{ $spot['lat'] }}" data-lng="{{ $spot['lng'] }}">
                        <span><strong>{{ $spot['quartier'] }}</strong><br><span class="text-muted">dernier dépôt {{ $spot['last_at']->diffForHumans() }}</span></span>
                        <span class="badge text-bg-danger rounded-pill">{{ $spot['count'] }}×</span>
                    </button>
                @empty
                    <div class="list-group-item text-muted">Aucun point noir récurrent détecté.</div>
                @endforelse
            </div>
            <div class="card-body small text-muted pt-2">Au moins 3 dépôts vérifiés dans un rayon de 150 m en 90 jours.</div>
        </div>
    </div>
    <div class="col-lg-9 order-1 order-lg-2">
        <div id="map" class="map map-tall"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const points = @js($points);
    const zones = @js($zones);
    const hotspots = @js(collect($hotspots)->map(fn ($h) => ['lat' => $h['lat'], 'lng' => $h['lng'], 'count' => $h['count'], 'quartier' => $h['quartier']]));

    const map = CC.baseMap('map', { zoom: 14, scrollWheelZoom: true });

    const zoneLayer = L.layerGroup(zones.map((z) => L.circle([z.lat, z.lng], {
        radius: 260, color: z.hex, weight: 1.5, fillColor: z.hex, fillOpacity: .12,
    }).bindPopup(`<strong>${CC.escape(z.name)}</strong><br>Indice de propreté : <b>${z.score}/100</b> (${CC.escape(z.label)})<br><a href="${z.url}">Voir la fiche du quartier</a>`))).addTo(map);

    const hotspotLayer = L.layerGroup(hotspots.map((h) => L.circle([h.lat, h.lng], {
        radius: 150, color: '#dc2626', weight: 2, dashArray: '6 6', fillOpacity: .05,
    }).bindPopup(`<strong>Point noir récurrent</strong><br>${h.count} dépôts vérifiés en 90 jours · ${CC.escape(h.quartier || '')}`))).addTo(map);

    const markers = points.map((p) => {
        const m = L.marker([p.lat, p.lng], { icon: CC.pin(p.color, p.open ? 18 : 14) });
        const photo = p.photo ? `<img src="${p.photo}" alt="" style="width:100%;max-height:120px;object-fit:cover;border-radius:8px" class="mb-2">` : '';
        m.bindPopup(`${photo}<strong>${CC.escape(p.category)}</strong><br>${CC.escape(p.quartier || '')} · ${CC.escape(p.date)}<br><span style="color:${p.color};font-weight:700">● ${CC.escape(p.statusLabel)}</span>`);
        m.status = p.status;
        return m;
    });
    const reportLayer = L.layerGroup(markers).addTo(map);
    CC.fit(map, points.map((p) => [p.lat, p.lng]), 15);

    function refresh() {
        const active = [...document.querySelectorAll('.status-filter:checked')].map((i) => i.value);
        reportLayer.clearLayers();
        markers.filter((m) => active.includes(m.status)).forEach((m) => reportLayer.addLayer(m));
    }
    document.querySelectorAll('.status-filter').forEach((i) => i.addEventListener('change', refresh));
    document.getElementById('layer-zones').addEventListener('change', (e) => e.target.checked ? zoneLayer.addTo(map) : map.removeLayer(zoneLayer));
    document.getElementById('layer-hotspots').addEventListener('change', (e) => e.target.checked ? hotspotLayer.addTo(map) : map.removeLayer(hotspotLayer));
    document.querySelectorAll('.hotspot-link').forEach((b) => b.addEventListener('click', () => {
        map.setView([parseFloat(b.dataset.lat), parseFloat(b.dataset.lng)], 17);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }));

    const legend = L.control({ position: 'bottomleft' });
    legend.onAdd = () => {
        const div = L.DomUtil.create('div', 'map-legend');
        div.innerHTML = '<i style="background:#dc2626"></i>À traiter<br><i style="background:#2563eb"></i>En cours<br><i style="background:#16a34a"></i>Nettoyé';
        return div;
    };
    legend.addTo(map);
});
</script>
@endpush
