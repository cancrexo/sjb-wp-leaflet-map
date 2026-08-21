/**
 * Inicializa los mapas Leaflet renderizados por el shortcode.
 * Compatible con footer y con carga async/defer.
 *
 * Modos de texto (data-marker-mode): hover | click | both | always
 * «always» usa tooltip permanente de Leaflet ({ permanent: true }).
 */
(() => {
    'use strict';

    /**
     * Asocia texto al marcador según el modo de visualización.
     *
     * @param {L.Marker} marker Marcador Leaflet.
     * @param {string} text HTML/texto.
     * @param {string} mode Modo.
     */
    const bindMarkerText = (marker, text, mode) => {
        const content = (text || '').trim();
        if (content === '') {
            return;
        }

        const display = mode || 'both';

        if (display === 'always') {
            marker.bindTooltip(content, {
                permanent: true,
                direction: 'top',
                opacity: 0.95,
            });
            return;
        }

        if (display === 'hover' || display === 'both') {
            marker.bindTooltip(content);
        }

        if (display === 'click' || display === 'both') {
            marker.bindPopup(content);
        }
    };

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
            bindMarkerText(marker, el.dataset.markerText || '', el.dataset.markerMode || 'both');
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMaps);
    } else {
        initMaps();
    }
})();
