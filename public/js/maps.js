/* Petites aides Leaflet partagées par toutes les cartes CleanComores. */
window.CC = window.CC || {};

(function (CC) {
    // Centre par défaut : Moroni (Ngazidja).
    CC.MORONI = [-11.7036, 43.2590];

    CC.baseMap = function (el, options) {
        options = options || {};
        const map = L.map(el, { scrollWheelZoom: options.scrollWheelZoom ?? false, zoomControl: true })
            .setView(options.center || CC.MORONI, options.zoom || 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; contributeurs <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);
        // Le conteneur peut changer de taille après le chargement (polices, cartes Bootstrap) :
        // on recalcule pour éviter une carte à moitié grise.
        const refresh = () => {
            map.invalidateSize();
            if (map._ccFit) CC.fit(map, map._ccFit.latlngs, map._ccFit.maxZoom);
        };
        if ('ResizeObserver' in window) {
            new ResizeObserver(refresh).observe(map.getContainer());
        }
        window.addEventListener('load', refresh);
        (CC.instances = CC.instances || []).push(map);
        return map;
    };

    CC.pin = function (color, size) {
        size = size || 18;
        return L.divIcon({
            className: '',
            html: '<div class="pin" style="background:' + color + ';width:' + size + 'px;height:' + size + 'px"></div>',
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
            popupAnchor: [0, -size / 2],
        });
    };

    CC.numberPin = function (n, color) {
        return L.divIcon({
            className: '',
            html: '<div class="pin-num" style="background:' + color + '">' + n + '</div>',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -15],
        });
    };

    CC.escape = function (value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };

    CC.fit = function (map, latlngs, maxZoom) {
        if (!latlngs.length) return;
        map._ccFit = { latlngs, maxZoom };
        // Sans animation : un redimensionnement pendant l'animation l'annulerait.
        if (latlngs.length === 1) { map.setView(latlngs[0], maxZoom || 16, { animate: false }); return; }
        map.fitBounds(L.latLngBounds(latlngs), { padding: [30, 30], maxZoom: maxZoom || 16, animate: false });
    };

    // Distance en mètres (haversine) — utilisée pour proposer le quartier le plus proche.
    CC.distance = function (a, b) {
        const R = 6371000, toRad = (d) => d * Math.PI / 180;
        const dLat = toRad(b[0] - a[0]), dLng = toRad(b[1] - a[1]);
        const h = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(a[0])) * Math.cos(toRad(b[0])) * Math.sin(dLng / 2) ** 2;
        return 2 * R * Math.asin(Math.sqrt(h));
    };

    /**
     * Sélecteur de position : clic sur la carte, marqueur déplaçable, bouton « Ma position ».
     * Remplit les champs latitude / longitude et propose le quartier le plus proche.
     */
    CC.locationPicker = function (opts) {
        const map = CC.baseMap(opts.map, { zoom: 15, center: opts.center, scrollWheelZoom: true });
        const lat = document.getElementById(opts.lat);
        const lng = document.getElementById(opts.lng);
        const quartierSelect = opts.quartier ? document.getElementById(opts.quartier) : null;
        const status = opts.status ? document.getElementById(opts.status) : null;
        let marker = null;

        function set(latlng, fromUser) {
            lat.value = latlng.lat.toFixed(7);
            lng.value = latlng.lng.toFixed(7);
            if (!marker) {
                marker = L.marker(latlng, { draggable: true }).addTo(map);
                marker.on('dragend', () => set(marker.getLatLng(), true));
            } else {
                marker.setLatLng(latlng);
            }
            if (status) status.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Position enregistrée';
            if (quartierSelect && opts.quartiers && fromUser) {
                let best = null, bestD = Infinity;
                opts.quartiers.forEach((q) => {
                    const d = CC.distance([latlng.lat, latlng.lng], [q.lat, q.lng]);
                    if (d < bestD) { bestD = d; best = q; }
                });
                if (best && bestD < 3000) quartierSelect.value = best.id;
            }
        }

        if (lat.value && lng.value) {
            const initial = L.latLng(parseFloat(lat.value), parseFloat(lng.value));
            set(initial, false);
            map.setView(initial, 16);
        }

        map.on('click', (e) => set(e.latlng, true));

        if (opts.locate) {
            document.getElementById(opts.locate).addEventListener('click', function () {
                if (!navigator.geolocation) { alert('La géolocalisation n\'est pas disponible sur cet appareil.'); return; }
                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Localisation…';
                navigator.geolocation.getCurrentPosition((pos) => {
                    const ll = L.latLng(pos.coords.latitude, pos.coords.longitude);
                    set(ll, true);
                    map.setView(ll, 17);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-crosshair"></i> Ma position';
                }, () => {
                    alert('Impossible d\'obtenir votre position. Touchez la carte pour indiquer l\'endroit.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-crosshair"></i> Ma position';
                }, { enableHighAccuracy: true, timeout: 10000 });
            });
        }

        if (quartierSelect && opts.quartiers) {
            quartierSelect.addEventListener('change', () => {
                const q = opts.quartiers.find((x) => String(x.id) === quartierSelect.value);
                if (q && !marker) map.setView([q.lat, q.lng], 16);
            });
        }

        setTimeout(() => map.invalidateSize(), 200);
        return map;
    };

    /** Itinéraire numéroté (agent et responsables). */
    CC.routeMap = function (el, points, color) {
        const map = CC.baseMap(el, { zoom: 14 });
        const latlngs = points.map((p) => [p.lat, p.lng]);
        if (latlngs.length > 1) {
            L.polyline(latlngs, { color: color || '#0f766e', weight: 4, opacity: .75, dashArray: '8 8' }).addTo(map);
        }
        points.forEach((p) => {
            const c = p.done ? '#16a34a' : (p.type === 'intervention' ? '#d97706' : (color || '#0f766e'));
            L.marker([p.lat, p.lng], { icon: CC.numberPin(p.n, c) }).addTo(map)
                .bindPopup('<strong>' + CC.escape(p.time + ' · ' + p.title) + '</strong><br><span class="text-muted">' + CC.escape(p.status) + '</span>');
        });
        CC.fit(map, latlngs, 15);
        return map;
    };
})(window.CC);
