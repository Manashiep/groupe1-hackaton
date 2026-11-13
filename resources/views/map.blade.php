@extends('layouts.app')

@section('title', 'Carte')

@include('partials.leaflet')

@section('content')
<div class="map-screen">
    <div id="map" role="application" aria-label="Carte des dépôts sauvages"></div>

    <div class="map-legend">
        @foreach (\App\Enums\ReportStatus::visibleOnMap() as $status)
            <span class="pill"><span class="dot" style="background: {{ $status->hex() }}"></span>{{ $status->getLabel() }}</span>
        @endforeach
    </div>

    @if (session('success'))
        <div class="position-absolute start-0 end-0 px-3" style="top: 56px; z-index: 600">@include('partials.flash')</div>
    @endif

    <button type="button" class="locate" id="locate" aria-label="Me localiser"><i class="bi bi-crosshair"></i></button>
    <a href="{{ route('reports.create') }}" class="btn btn-sun fab"><i class="bi bi-camera-fill me-2"></i>Signaler</a>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const points = @js($points);
    const map = CC.baseMap('map', { zoom: 14, zoomControl: false });

    points.forEach((p) => {
        const photo = p.photo ? `<img src="${p.photo}" alt="">` : '';
        const cleanup = p.cleanup ? `<div class="mt-1"><i class="bi bi-truck"></i> ${CC.escape(p.truck || '')}<br><strong>${CC.escape(p.cleanup)}</strong></div>` : '';
        const description = p.description ? `<div class="text-muted mt-1">${CC.escape(p.description)}</div>` : '';
        L.marker([p.lat, p.lng], { icon: CC.pin(p.color) }).addTo(map).bindPopup(
            `${photo}<span style="color:${p.color};font-weight:800">● ${CC.escape(p.status)}</span>
             <span class="text-muted"> · ${CC.escape(p.date)}</span>${description}${cleanup}`
        );
    });

    if (points.length) CC.fit(map, points.map((p) => [p.lat, p.lng]), 15);

    let me = null;
    document.getElementById('locate').addEventListener('click', () => CC.locate((ll) => {
        me ? me.setLatLng(ll) : (me = L.circleMarker(ll, { radius: 8, color: '#fff', weight: 3, fillColor: '#2563eb', fillOpacity: 1 }).addTo(map));
        map.setView(ll, 16);
    }, () => alert('Impossible d\'obtenir votre position.')));
});
</script>
@endpush
