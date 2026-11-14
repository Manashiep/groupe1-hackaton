<x-filament-widgets::widget>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

    @php($points = $this->getPoints())

    <x-filament::section>
        <x-slot name="heading">Carte des signalements</x-slot>
        <x-slot name="description">{{ count($points) }} point(s) : ce que voient les habitants. Cliquez sur un point pour ouvrir sa fiche.</x-slot>

        <div style="display: flex; flex-wrap: wrap; gap: .4rem 1rem; font-size: .8rem; margin-bottom: .75rem; opacity: .85">
            @foreach (['#d64a32' => 'À nettoyer', '#d9941c' => 'Pris en charge', '#3567c4' => 'En cours', '#2e8b57' => 'Nettoyé (24 h)'] as $hex => $label)
                <span style="display: inline-flex; align-items: center; gap: .35rem"><span style="width: .7rem; height: .7rem; border-radius: 50%; background: {{ $hex }}"></span>{{ $label }}</span>
            @endforeach
            <span style="display: inline-flex; align-items: center; gap: .35rem"><span style="width: .7rem; height: .7rem; border-radius: 2px; background: #3c4a5e"></span>Demande de collecte</span>
        </div>

        <div
            wire:ignore
            x-data="{
                init() {
                    const points = @js($points);
                    // Les textes viennent des habitants : on les affiche comme du texte, jamais comme du HTML.
                    const esc = (value) => { const div = document.createElement('div'); div.textContent = value ?? ''; return div.innerHTML; };
                    const map = L.map(this.$refs.map).setView([-11.7038, 43.2520], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);

                    points.forEach((p) => {
                        const shape = p.kind === 'pickup' ? 'border-radius:3px' : 'border-radius:50%';
                        const icon = L.divIcon({ className: '', html: `<div style='width:16px;height:16px;${shape};background:${esc(p.color)};border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)'></div>`, iconSize: [16, 16], iconAnchor: [8, 8] });
                        const photo = p.photo ? `<img src='${esc(p.photo)}' alt='' style='width:100%;height:110px;object-fit:cover;border-radius:6px;margin-bottom:6px'>` : '';
                        const taker = p.taker ? `<div style='margin-top:4px'>Par <b>${esc(p.taker)}</b>${p.planned ? ' · ' + esc(p.planned) : ''}</div>` : '';
                        L.marker([p.lat, p.lng], { icon }).addTo(map).bindPopup(
                            `<div style='width:220px'>${photo}<b style='color:${esc(p.color)}'>${esc(p.status)}</b> · <span style='opacity:.7'>${esc(p.date)}</span>`
                            + (p.description ? `<div style='margin-top:4px'>${esc(p.description)}</div>` : '')
                            + `${taker}<a href='${esc(p.url)}' style='display:inline-block;margin-top:6px;font-weight:600'>Ouvrir la fiche →</a></div>`
                        );
                    });

                    if (points.length) {
                        map.fitBounds(L.latLngBounds(points.map((p) => [p.lat, p.lng])), { padding: [30, 30], maxZoom: 16 });
                    }
                    setTimeout(() => map.invalidateSize(), 200);
                },
            }"
        >
            <div x-ref="map" style="height: 460px; border-radius: .75rem; overflow: hidden; z-index: 0"></div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
