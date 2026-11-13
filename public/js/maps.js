/* Aides Leaflet partagées par les écrans habitants et agents. */
window.CC = window.CC || {};

(function (CC) {
    CC.MORONI = [-11.7038, 43.2520];

    CC.baseMap = function (el, options) {
        options = options || {};
        const map = L.map(el, { zoomControl: options.zoomControl ?? true })
            .setView(options.center || CC.MORONI, options.zoom || 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);

        // Le conteneur peut changer de taille après le chargement : on recalcule la carte.
        const refresh = () => {
            map.invalidateSize();
            if (map._ccFit) CC.fit(map, map._ccFit.latlngs, map._ccFit.maxZoom);
        };
        if ('ResizeObserver' in window) new ResizeObserver(refresh).observe(map.getContainer());
        window.addEventListener('load', refresh);
        return map;
    };

    CC.pin = function (color) {
        return L.divIcon({ className: '', html: '<div class="pin" style="background:' + color + '"></div>', iconSize: [20, 20], iconAnchor: [10, 10], popupAnchor: [0, -10] });
    };

    CC.numberPin = function (n, color) {
        return L.divIcon({ className: '', html: '<div class="pin-num" style="background:' + color + '">' + n + '</div>', iconSize: [30, 30], iconAnchor: [15, 15], popupAnchor: [0, -15] });
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
        map.fitBounds(L.latLngBounds(latlngs), { padding: [40, 40], maxZoom: maxZoom || 16, animate: false });
    };

    CC.locate = function (onSuccess, onError) {
        if (!navigator.geolocation) { onError && onError(); return; }
        navigator.geolocation.getCurrentPosition(
            (pos) => onSuccess(L.latLng(pos.coords.latitude, pos.coords.longitude)),
            () => onError && onError(),
            { enableHighAccuracy: true, timeout: 10000 },
        );
    };

    /**
     * Choix d'un endroit : la position GPS est proposée d'office, un appui sur la carte la corrige.
     */
    CC.locationPicker = function (opts) {
        const map = CC.baseMap(opts.map, { zoom: 15 });
        const lat = document.getElementById(opts.lat);
        const lng = document.getElementById(opts.lng);
        const status = document.getElementById(opts.status);
        let marker = null;

        function set(latlng) {
            lat.value = latlng.lat.toFixed(7);
            lng.value = latlng.lng.toFixed(7);
            if (!marker) {
                marker = L.marker(latlng, { draggable: true }).addTo(map);
                marker.on('dragend', () => set(marker.getLatLng()));
            } else {
                marker.setLatLng(latlng);
            }
            status.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Endroit enregistré — vous pouvez déplacer le repère.';
        }

        if (lat.value && lng.value) {
            const initial = L.latLng(+lat.value, +lng.value);
            set(initial);
            map.setView(initial, 17, { animate: false });
        } else {
            status.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Recherche de votre position…';
            CC.locate((ll) => { set(ll); map.setView(ll, 17, { animate: false }); },
                () => { status.textContent = 'Touchez la carte à l\'endroit exact.'; });
        }

        map.on('click', (e) => set(e.latlng));
        return map;
    };
})(window.CC);
