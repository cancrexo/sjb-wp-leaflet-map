/**
 * Inicializa los mapas Leaflet renderizados por el shortcode.
 * Compatible con footer y con carga async/defer.
 */
(() => {
    'use strict';

    const initMaps = () => {
        if (typeof L === 'undefined') {
            return;
        }

        document.querySelectorAll('.sjb-leaflet-map').forEach((el) => {
            const lat = parseFloat(el.dataset.lat);
            const lng = parseFloat(el.dataset.lng);
            const zoom = parseInt(el.dataset.zoom, 10);

            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                return;
            }

            const map = L.map(el).setView([lat, lng], Number.isNaN(zoom) ? 13 : zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(map);

            const markerLat = parseFloat(el.dataset.markerLat);
            const markerLng = parseFloat(el.dataset.markerLng);

            if (Number.isNaN(markerLat) || Number.isNaN(markerLng)) {
                return;
            }

            const marker = L.marker([markerLat, markerLng]).addTo(map);
            const text = (el.dataset.markerText || '').trim();

            if (text !== '') {
                marker.bindPopup(text);
                marker.bindTooltip(text);
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMaps);
    } else {
        initMaps();
    }
})();
