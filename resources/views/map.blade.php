@extends('layouts.app')

@section('title', 'Carte')

@include('partials.leaflet')

@section('content')
<div class="map-screen">
    <div id="map" role="application" aria-label="Carte des dépôts à nettoyer et des demandes de collecte"></div>

    <div class="map-legend">
        <span class="pill"><span class="dot" style="color:#d64a32"></span>À nettoyer</span>
        <span class="pill"><span class="dot" style="color:#d9941c"></span>Pris en charge</span>
        <span class="pill"><span class="dot" style="color:#3567c4"></span>En cours</span>
        <span class="pill"><span class="dot" style="color:#2e8b57"></span>Nettoyé (24 h)</span>
        <span class="pill"><span class="dot" style="color:#3c4a5e;border-radius:3px"></span>Demande de collecte</span>
    </div>

    @if (session('success'))
        <div class="map-flash">@include('partials.flash')</div>
    @endif

    <div class="map-actions">
        <button type="button" class="round-btn" id="locate" aria-label="Me localiser"><i class="bi bi-crosshair"></i></button>
    </div>
    <a href="{{ route('reports.create') }}" class="btn btn-primary btn-lg map-cta d-none d-lg-inline-flex align-items-center gap-2"><i class="bi bi-camera-fill"></i> Signaler un dépôt</a>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const points = @js($points);
    const map = CC.baseMap('map', { zoom: 14, zoomControl: false });

    points.forEach((p) => {
        const photo = p.photo ? `<img src="${p.photo}" alt="">` : '';
        const who = p.taker
            ? `<div class="who"><i class="bi bi-person-check-fill text-warning"></i> <strong>${CC.escape(p.taker)}</strong><br><span class="text-muted">${CC.escape(p.planned || '')}</span></div>`
            : '';
        const action = p.taker ? 'Voir le détail' : (p.kind === 'pickup' ? 'Je peux ramasser' : 'Je m\'en occupe');
        L.marker([p.lat, p.lng], { icon: CC.pin(p.color, p.kind === 'pickup') }).addTo(map).bindPopup(
            `<div class="pop">${photo}<div class="body">
                <span class="pill ${p.statusClass}"><span class="dot"></span>${CC.escape(p.status)}</span>
                <span class="text-muted small ms-1">${CC.escape(p.date)}</span>
                ${p.description ? `<p class="desc">${CC.escape(p.description)}</p>` : ''}
                ${who}
                <a href="${p.url}" class="btn btn-sm ${p.taker ? 'btn-soft' : 'btn-primary'}">${action}</a>
            </div></div>`,
            { maxWidth: 260, minWidth: 240, autoPanPaddingTopLeft: [12, 110], autoPanPaddingBottomRight: [70, 24] }
        );
    });

    if (points.length) CC.fit(map, points.map((p) => [p.lat, p.lng]), 15);

    let me = null;
    document.getElementById('locate').addEventListener('click', () => CC.locate((ll) => {
        me ? me.setLatLng(ll) : (me = L.circleMarker(ll, { radius: 9, color: '#fff', weight: 3, fillColor: '#1f5fd0', fillOpacity: 1 }).addTo(map));
        map.setView(ll, 16);
    }, () => alert('Impossible d\'obtenir votre position.')));
});
</script>
@endpush
