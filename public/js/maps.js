/* Aides partagées : cartes Leaflet et compression des photos avant envoi. */
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

    /** Rond pour un dépôt sauvage, carré pour une demande de collecte. */
    CC.pin = function (color, square) {
        return L.divIcon({
            className: '',
            html: '<div class="pin' + (square ? ' pin-square' : '') + '" style="background:' + color + '"></div>',
            iconSize: [22, 22], iconAnchor: [11, 11], popupAnchor: [0, -12],
        });
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
        map.fitBounds(L.latLngBounds(latlngs), { padding: [48, 48], maxZoom: maxZoom || 16, animate: false });
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
            status.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Endroit enregistré. Glissez le repère pour l\'ajuster.';
        }

        function useCurrentPosition() {
            if (!window.isSecureContext) {
                status.textContent = 'Le GPS demande une connexion sécurisée (https). Touchez la carte à l\'endroit exact.';
                return;
            }
            status.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Recherche de votre position…';
            CC.locate((ll) => { set(ll); map.setView(ll, 17, { animate: false }); },
                () => { status.textContent = 'Position introuvable (GPS désactivé ou refusé). Touchez la carte à l\'endroit exact.'; });
        }

        if (lat.value && lng.value) {
            const initial = L.latLng(+lat.value, +lng.value);
            set(initial);
            map.setView(initial, 17, { animate: false });
        } else {
            useCurrentPosition();
        }

        if (opts.locateButton) {
            document.getElementById(opts.locateButton).addEventListener('click', useCurrentPosition);
        }

        map.on('click', (e) => set(e.latlng));
        return map;
    };

    /**
     * Réduit une photo (1600 px, JPEG 80 %) avant l'envoi : plus rapide en 3G et sous la limite du serveur.
     */
    CC.compressPhotoInput = function (input, onPreview) {
        input.addEventListener('change', async () => {
            const file = input.files[0];
            if (!file || !file.type.startsWith('image/')) return;
            onPreview && onPreview(URL.createObjectURL(file));
            try {
                const bitmap = await createImageBitmap(file);
                const scale = Math.min(1, 1600 / Math.max(bitmap.width, bitmap.height));
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(bitmap.width * scale);
                canvas.height = Math.round(bitmap.height * scale);
                canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.8));
                if (!blob || blob.size >= file.size) return;
                const transfer = new DataTransfer();
                transfer.items.add(new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }));
                input.files = transfer.files;
            } catch (e) {
                // Navigateur trop ancien : on envoie la photo d'origine.
            }
        });
    };
})(window.CC);
