/**
 * Inicializa los mapas Leaflet renderizados por los shortcodes.
 * Compatible con footer y con carga async/defer.
 *
 * - Colección: data-markers = JSON [{lat,lng,text,mode}, ...]
 * - Mapa simple: data-marker-lat/lng/text + data-marker-mode
 * Modos de texto: hover | click | both | always
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

    /**
     * Lee la lista de marcadores desde data-markers (JSON) o el marcador suelto.
     *
     * @param {HTMLElement} el Contenedor del mapa.
     * @returns {Array<{lat:number,lng:number,text:string,mode:string}>}
     */
    const readMarkers = (el) => {
        const raw = el.getAttribute('data-markers');
        if (raw) {
            try {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) {
                    return parsed;
                }
            } catch (e) {
                // JSON inválido: se ignora y se prueba el marcador suelto.
            }
        }

        const markerLat = parseFloat(el.dataset.markerLat);
        const markerLng = parseFloat(el.dataset.markerLng);
        if (Number.isNaN(markerLat) || Number.isNaN(markerLng)) {
            return [];
        }

        return [
            {
                lat: markerLat,
                lng: markerLng,
                text: el.dataset.markerText || '',
                mode: el.dataset.markerMode || 'both',
            },
        ];
    };

    const initMaps = () => {
        if (typeof L === 'undefined') {
            return;
        }

        document.querySelectorAll('.sjb-leaflet-map').forEach((el) => {
            const lat = parseFloat(el.dataset.lat);
            const lng = parseFloat(el.dataset.lng);
            const zoomRaw = parseInt(el.dataset.zoom, 10);
            let zoom = Number.isNaN(zoomRaw) ? 13 : zoomRaw;
            if (zoom < 0) {
                zoom = 0;
            } else if (zoom > 19) {
                zoom = 19;
            }

            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                return;
            }

            const map = L.map(el).setView([lat, lng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(map);

            readMarkers(el).forEach((item) => {
                const mLat = parseFloat(item.lat);
                const mLng = parseFloat(item.lng);
                if (Number.isNaN(mLat) || Number.isNaN(mLng)) {
                    return;
                }

                const marker = L.marker([mLat, mLng]).addTo(map);
                bindMarkerText(marker, item.text || '', item.mode || 'both');
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMaps);
    } else {
        initMaps();
    }
})();
