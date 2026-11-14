<x-filament-panels::page>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <style>
        .route-grid { display: grid; gap: 1.5rem; grid-template-columns: minmax(0, 1fr); }
        @media (min-width: 1024px) { .route-grid { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); } }
        .route-map { height: 560px; border-radius: .75rem; overflow: hidden; z-index: 0; }
        .route-pin { width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; color: #fff; font-weight: 800; font-size: 13px; border: 3px solid #fff; box-shadow: 0 1px 5px rgba(0,0,0,.35); cursor: grab; }
        .route-row { display: flex; align-items: center; gap: .5rem; padding: .5rem 0; border-bottom: 1px solid rgba(127,127,127,.15); }
        .route-row:last-child { border-bottom: 0; }
        .route-n { width: 28px; height: 28px; flex-shrink: 0; border-radius: 50%; display: grid; place-items: center; color: #fff; font-weight: 800; font-size: 12px; }
    </style>
    <script>
        // Les noms de villages et de points sont saisis à la main : on les affiche comme du texte, jamais comme du HTML.
        const escapeHtml = (value) => { const div = document.createElement('div'); div.textContent = value ?? ''; return div.innerHTML; };

        window.routeEditor = (config) => ({
            map: null,
            layer: null,
            init() {
                this.map = L.map(this.$refs.map).setView(config.center, 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(this.map);
                config.villages.forEach((v) => L.circleMarker([v.lat, v.lng], { radius: 7, color: '#0b2a5b', weight: 2, fillColor: '#f5c518', fillOpacity: .9 }).addTo(this.map).bindTooltip(escapeHtml(v.name), { permanent: true, direction: 'top', offset: [0, -6] }));
                this.layer = L.layerGroup().addTo(this.map);
                this.map.on('click', (e) => this.$wire.addStop(e.latlng.lat, e.latlng.lng));
                this.draw(config.stops, true);
                setTimeout(() => this.map.invalidateSize(), 200);
            },
            draw(stops, fit = false) {
                this.layer.clearLayers();
                if (stops.length > 1) {
                    L.polyline(stops.map((s) => [s.lat, s.lng]), { color: config.color, weight: 4, opacity: .7, dashArray: '8 8' }).addTo(this.layer);
                }
                stops.forEach((s) => {
                    const icon = L.divIcon({ className: '', html: `<div class="route-pin" style="background:${config.color}">${s.n}</div>`, iconSize: [30, 30], iconAnchor: [15, 15] });
                    L.marker([s.lat, s.lng], { icon, draggable: true }).addTo(this.layer)
                        .bindTooltip(escapeHtml(s.label))
                        .on('dragend', (e) => { const ll = e.target.getLatLng(); this.$wire.moveStop(s.id, ll.lat, ll.lng); });
                });
                if (fit && stops.length) {
                    this.map.fitBounds(L.latLngBounds(stops.map((s) => [s.lat, s.lng])), { padding: [40, 40], maxZoom: 17 });
                }
            },
        });
    </script>

    <div class="route-grid">
        <x-filament::section>
            <x-slot name="description">
                Cliquez sur la carte pour ajouter un point de collecte. Faites glisser un point pour le déplacer.
                L'agent du camion voit cette tournée dans son application.
            </x-slot>
            <div
                wire:ignore
                x-data="routeEditor(@js(['stops' => $this->stopsPayload(), 'color' => $truck->color, 'villages' => $truck->villages->map(fn ($v) => ['name' => $v->name, 'lat' => $v->latitude, 'lng' => $v->longitude])->values(), 'center' => [$truck->villages->first()?->latitude ?? -11.7038, $truck->villages->first()?->longitude ?? 43.2520]]))"
                x-on:route-updated.window="draw($event.detail.stops)"
            >
                <div x-ref="map" class="route-map"></div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Ordre de passage</x-slot>
            <x-slot name="description">{{ $stops->count() }} point(s) · {{ $truck->villages->pluck('name')->join(', ') }}</x-slot>

            @forelse ($stops as $stop)
                <div class="route-row" wire:key="stop-{{ $stop->id }}">
                    <span class="route-n" style="background: {{ $truck->color }}">{{ $stop->position }}</span>
                    <x-filament::input.wrapper class="flex-1" style="flex: 1">
                        <x-filament::input
                            type="text"
                            value="{{ $stop->label }}"
                            placeholder="Point {{ $stop->position }}"
                            wire:change="renameStop({{ $stop->id }}, $event.target.value)"
                        />
                    </x-filament::input.wrapper>
                    <x-filament::icon-button icon="heroicon-m-arrow-up" color="gray" label="Monter" wire:click="moveStopUp({{ $stop->id }})" :disabled="$loop->first" />
                    <x-filament::icon-button icon="heroicon-m-arrow-down" color="gray" label="Descendre" wire:click="moveStopDown({{ $stop->id }})" :disabled="$loop->last" />
                    <x-filament::icon-button icon="heroicon-m-trash" color="danger" label="Supprimer" wire:click="deleteStop({{ $stop->id }})" wire:confirm="Supprimer ce point de collecte ?" />
                </div>
            @empty
                <p style="opacity: .7">Aucun point pour l'instant : cliquez sur la carte pour tracer la tournée.</p>
            @endforelse
        </x-filament::section>
    </div>
</x-filament-panels::page>
