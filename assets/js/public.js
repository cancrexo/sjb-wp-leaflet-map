/**
 * Inicializa los mapas Leaflet renderizados por los shortcodes.
 * Compatible con footer y con carga async/defer.
 *
 * - Colección: data-markers = JSON [{lat,lng,text,mode}, ...]
 * - Mapa simple: data-marker-lat/lng/text + data-marker-mode
 * - Icono: data-icon-url (+ width/height); vacío = Leaflet default
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

    /**
     * Icono Leaflet desde data-icon-* del contenedor (o default nativo).
     *
     * @param {HTMLElement} el Contenedor.
     * @returns {L.Icon|undefined}
     */
    const readMapIcon = (el) => {
        const url = (el.dataset.iconUrl || '').trim();
        if (!url) {
            return undefined;
        }

        let width = parseInt(el.dataset.iconWidth, 10);
        let height = parseInt(el.dataset.iconHeight, 10);
        if (Number.isNaN(width) || width < 1) {
            width = 25;
        }
        if (Number.isNaN(height) || height < 1) {
            height = 41;
        }

        // Limitar tamaño visual en mapa (imágenes grandes de la media library).
        const maxSide = 48;
        if (width > maxSide || height > maxSide) {
            const scale = maxSide / Math.max(width, height);
            width = Math.max(1, Math.round(width * scale));
            height = Math.max(1, Math.round(height * scale));
        }

        return L.icon({
            iconUrl: url,
            iconSize: [width, height],
            iconAnchor: [Math.round(width / 2), height],
            popupAnchor: [0, -height],
            tooltipAnchor: [0, -Math.round(height / 2)],
        });
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

            const icon = readMapIcon(el);

            readMarkers(el).forEach((item) => {
                const mLat = parseFloat(item.lat);
                const mLng = parseFloat(item.lng);
                if (Number.isNaN(mLat) || Number.isNaN(mLng)) {
                    return;
                }

                const opts = icon ? { icon } : undefined;
                const marker = L.marker([mLat, mLng], opts).addTo(map);
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
