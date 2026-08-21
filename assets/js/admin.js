/**
 * Admin AJAX: formularios, toasts y navegación post-éxito.
 *
 * @package sjb-wp-leaflet-map
 */
(function () {
    'use strict';

    const cfg = window.sjbWpLeafletMapAdmin || {};
    const ajaxUrl = cfg.ajaxUrl || '';
    const nonce = cfg.nonce || '';
    const i18n = cfg.i18n || {};

    /**
     * Contenedor fijo de toasts (esquina superior derecha).
     */
    function getToastRoot() {
        let root = document.getElementById('sjb-leaflet-toast-root');
        if (!root) {
            root = document.createElement('div');
            root.id = 'sjb-leaflet-toast-root';
            root.className = 'sjb-leaflet-toast-root';
            root.setAttribute('aria-live', 'polite');
            document.body.appendChild(root);
        }
        return root;
    }

    /**
     * Muestra un toast de éxito o error (auto-dismiss).
     *
     * @param {string} message Texto.
     * @param {'success'|'error'} type Tipo visual.
     */
    function toast(message, type) {
        const root = getToastRoot();
        const el = document.createElement('div');
        el.className = 'sjb-leaflet-toast sjb-leaflet-toast--' + (type === 'error' ? 'error' : 'success');
        el.setAttribute('role', 'status');

        const text = document.createElement('span');
        text.className = 'sjb-leaflet-toast__text';
        text.textContent = message || (type === 'error' ? (i18n.errorGeneric || 'Error') : (i18n.okGeneric || 'OK'));
        el.appendChild(text);

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'sjb-leaflet-toast__close';
        closeBtn.setAttribute('aria-label', i18n.close || 'Cerrar');
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', () => dismiss(el));
        el.appendChild(closeBtn);

        root.appendChild(el);
        requestAnimationFrame(() => el.classList.add('is-visible'));

        const timer = setTimeout(() => dismiss(el), 4200);
        el._sjbTimer = timer;
    }

    /**
     * Quita el toast con animación.
     *
     * @param {HTMLElement} el Toast.
     */
    function dismiss(el) {
        if (!el || el._sjbLeaving) {
            return;
        }
        el._sjbLeaving = true;
        if (el._sjbTimer) {
            clearTimeout(el._sjbTimer);
        }
        el.classList.remove('is-visible');
        el.classList.add('is-leaving');
        setTimeout(() => el.remove(), 280);
    }

    /**
     * Llamada AJAX centralizada a admin-ajax.php.
     *
     * @param {string} action Sufijo (p. ej. save_settings).
     * @param {FormData|URLSearchParams|Record<string,string>} data Datos.
     * @returns {Promise<{ok:boolean, data:object, message:string}>}
     */
    async function ajax(action, data) {
        const body = data instanceof FormData ? data : new FormData();
        if (!(data instanceof FormData)) {
            const entries = data instanceof URLSearchParams
                ? data.entries()
                : Object.entries(data || {});
            for (const [key, value] of entries) {
                body.append(key, value == null ? '' : String(value));
            }
        }

        body.set('action', 'sjb_wp_leaflet_map_' + action);
        body.set('nonce', nonce);

        let json;
        try {
            const res = await fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body,
            });
            json = await res.json();
        } catch (err) {
            return {
                ok: false,
                data: {},
                message: i18n.networkError || 'Error de red. Inténtalo de nuevo.',
            };
        }

        const payload = json && typeof json.data === 'object' && json.data !== null ? json.data : {};
        const message = payload.message
            || (json && json.success ? (i18n.okGeneric || 'OK') : (i18n.errorGeneric || 'Error'));

        return {
            ok: !!(json && json.success),
            data: payload,
            message,
        };
    }

    /**
     * Tras éxito: toast y opcional redirect.
     *
     * @param {{ok:boolean, data:object, message:string}} result Resultado ajax().
     */
    function handleResult(result) {
        toast(result.message, result.ok ? 'success' : 'error');
        if (result.ok && result.data.redirect) {
            const modalEl = document.getElementById('sjb-modal-collection');
            if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                const instance = window.bootstrap.Modal.getInstance(modalEl);
                if (instance) {
                    instance.hide();
                }
            }
            // Breve pausa para que el toast se perciba antes de navegar.
            setTimeout(() => {
                window.location.href = result.data.redirect;
            }, 700);
        }
    }

    /**
     * Deshabilita botones del formulario mientras hay petición.
     *
     * @param {HTMLFormElement} form Formulario.
     * @param {boolean} busy Estado.
     */
    function setBusy(form, busy) {
        form.classList.toggle('is-sjb-busy', busy);
        form.querySelectorAll('button[type="submit"]').forEach((btn) => {
            btn.disabled = busy;
        });
    }

    /**
     * Acción AJAX desde el botón que disparó el submit.
     *
     * @param {HTMLFormElement} form Formulario.
     * @param {Event} event Evento submit.
     * @returns {string}
     */
    function resolveAction(form, event) {
        const submitter = event.submitter;
        if (submitter && submitter.getAttribute('data-sjb-action')) {
            return submitter.getAttribute('data-sjb-action');
        }
        return form.getAttribute('data-sjb-action') || '';
    }

    /**
     * Confirmación opcional antes de borrar.
     *
     * @param {HTMLFormElement} form Formulario.
     * @param {Event} event Evento submit.
     * @returns {boolean}
     */
    function confirmIfNeeded(form, event) {
        const submitter = event.submitter;
        const msg = (submitter && submitter.getAttribute('data-sjb-confirm'))
            || form.getAttribute('data-sjb-confirm');
        if (!msg) {
            return true;
        }
        return window.confirm(msg);
    }

    /**
     * Actualiza visibilidad y preview de un bloque icon-picker.
     *
     * @param {HTMLElement} picker Contenedor [data-sjb-icon-picker].
     * @param {{source?:string,attachmentId?:string,previewUrl?:string}} state Estado.
     */
    function setIconPickerState(picker, state) {
        if (!picker) {
            return;
        }

        const source = state.source || 'leaflet';
        const attachmentId = state.attachmentId || '0';
        const previewUrl = state.previewUrl || '';

        picker.querySelectorAll('.sjb-icon-source').forEach((radio) => {
            radio.checked = radio.value === source;
        });

        const idInput = picker.querySelector('.sjb-icon-attachment-id');
        if (idInput) {
            idInput.value = attachmentId;
        }

        const inheritPreview = picker.querySelector('[data-sjb-icon-inherit-preview]');
        const mediaRow = picker.querySelector('[data-sjb-icon-media-row]');
        const leafletPreview = picker.querySelector('[data-sjb-icon-leaflet-preview]');
        if (mediaRow) {
            mediaRow.classList.toggle('d-none', source !== 'media');
        }
        if (leafletPreview) {
            leafletPreview.classList.toggle('d-none', source !== 'leaflet');
        }
        if (inheritPreview) {
            inheritPreview.classList.toggle('d-none', source !== 'inherit');
        }

        const preview = picker.querySelector('[data-sjb-icon-preview]');
        const clearBtn = picker.querySelector('.sjb-icon-clear');
        const selectBtn = picker.querySelector('.sjb-icon-select');
        if (preview) {
            if (previewUrl) {
                preview.innerHTML = '<img src="' + previewUrl.replace(/"/g, '&quot;') + '" alt="">';
            } else {
                preview.innerHTML = '<span class="text-muted small">Ninguna imagen seleccionada</span>';
            }
        }
        if (clearBtn) {
            clearBtn.classList.toggle('d-none', !previewUrl);
        }
        if (selectBtn) {
            selectBtn.textContent = previewUrl
                ? (i18n.iconChange || 'Cambiar imagen')
                : (i18n.iconSelect || 'Seleccionar imagen');
        }
    }

    /**
     * Media Library + radios de origen de icono.
     *
     * @param {HTMLElement} root Ámbito (.sjb-leaflet-admin).
     */
    function initIconPickers(root) {
        root.querySelectorAll('[data-sjb-icon-picker]').forEach((picker) => {
            picker.querySelectorAll('.sjb-icon-source').forEach((radio) => {
                radio.addEventListener('change', () => {
                    if (!radio.checked) {
                        return;
                    }
                    const idInput = picker.querySelector('.sjb-icon-attachment-id');
                    const preview = picker.querySelector('[data-sjb-icon-preview] img');
                    setIconPickerState(picker, {
                        source: radio.value,
                        attachmentId: idInput ? idInput.value : '0',
                        previewUrl: preview ? preview.getAttribute('src') || '' : '',
                    });
                });
            });

            const selectBtn = picker.querySelector('.sjb-icon-select');
            const clearBtn = picker.querySelector('.sjb-icon-clear');

            if (selectBtn) {
                selectBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    if (!window.wp || !wp.media) {
                        toast(i18n.errorGeneric || 'Error', 'error');
                        return;
                    }

                    const frame = wp.media({
                        title: i18n.iconTitle || 'Icono del marcador',
                        button: { text: i18n.iconSelect || 'Seleccionar imagen' },
                        library: { type: 'image' },
                        multiple: false,
                    });

                    frame.on('select', () => {
                        const file = frame.state().get('selection').first().toJSON();
                        const idInput = picker.querySelector('.sjb-icon-attachment-id');
                        const sourceMedia = picker.querySelector('.sjb-icon-source[value="media"]');
                        if (sourceMedia) {
                            sourceMedia.checked = true;
                        }
                        setIconPickerState(picker, {
                            source: 'media',
                            attachmentId: String(file.id || 0),
                            previewUrl: (file.sizes && file.sizes.thumbnail && file.sizes.thumbnail.url)
                                || file.url
                                || '',
                        });
                        if (idInput) {
                            idInput.value = String(file.id || 0);
                        }
                    });

                    frame.open();
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    setIconPickerState(picker, {
                        source: 'media',
                        attachmentId: '0',
                        previewUrl: '',
                    });
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const wrap = document.querySelector('.sjb-leaflet-admin');
        if (!wrap || !ajaxUrl) {
            return;
        }

        initIconPickers(wrap);

        wrap.addEventListener('submit', async (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.classList.contains('sjb-ajax-form')) {
                return;
            }

            event.preventDefault();

            if (!confirmIfNeeded(form, event)) {
                return;
            }

            const action = resolveAction(form, event);
            if (!action) {
                toast(i18n.errorGeneric || 'Error', 'error');
                return;
            }

            setBusy(form, true);
            const body = new FormData(form);
            // Checkbox desmarcado no viaja en FormData; el servidor interpreta ausencia = 0.
            const result = await ajax(action, body);
            setBusy(form, false);
            handleResult(result);
        });

        // Modal colección: crear (vacío) o editar (datos del botón).
        const collectionModal = document.getElementById('sjb-modal-collection');
        const collectionForm = document.getElementById('sjb-form-collection');
        if (collectionModal && collectionForm instanceof HTMLFormElement) {
            const titleEl = document.getElementById('sjb-modal-collection-label');
            const submitEl = document.getElementById('sjb-form-collection-submit');
            const idEl = document.getElementById('sjb_collection_id');
            const nameEl = document.getElementById('sjb_collection_name');
            const slugEl = document.getElementById('sjb_collection_slug');
            const descEl = document.getElementById('sjb_collection_description');
            const iconPicker = collectionForm.querySelector('[data-sjb-icon-picker]');

            collectionModal.addEventListener('show.bs.modal', (event) => {
                const trigger = event.relatedTarget;
                const editId = trigger && trigger.getAttribute
                    ? (trigger.getAttribute('data-collection-id') || '')
                    : '';

                if (editId && editId !== '0') {
                    if (idEl) {
                        idEl.value = editId;
                    }
                    if (nameEl) {
                        nameEl.value = trigger.getAttribute('data-collection-name') || '';
                    }
                    if (slugEl) {
                        slugEl.value = trigger.getAttribute('data-collection-slug') || '';
                    }
                    if (descEl) {
                        descEl.value = trigger.getAttribute('data-collection-description') || '';
                    }
                    if (iconPicker) {
                        setIconPickerState(iconPicker, {
                            source: trigger.getAttribute('data-collection-icon-source') || 'inherit',
                            attachmentId: trigger.getAttribute('data-collection-icon-attachment') || '0',
                            previewUrl: trigger.getAttribute('data-collection-icon-preview') || '',
                        });
                    }
                    if (titleEl) {
                        titleEl.textContent = i18n.collectionEdit || 'Editar colección';
                    }
                    if (submitEl) {
                        submitEl.textContent = i18n.collectionSave || 'Guardar cambios';
                    }
                    return;
                }

                collectionForm.reset();
                if (idEl) {
                    idEl.value = '0';
                }
                if (iconPicker) {
                    setIconPickerState(iconPicker, {
                        source: 'inherit',
                        attachmentId: '0',
                        previewUrl: '',
                    });
                }
                if (titleEl) {
                    titleEl.textContent = i18n.collectionNew || 'Nueva colección';
                }
                if (submitEl) {
                    submitEl.textContent = i18n.collectionCreate || 'Crear colección';
                }
            });
        }

        initMarkersTable(wrap);

        // Stubs: exportar / duplicar colección (solo aviso de momento).
        wrap.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }
            const exportBtn = target.closest('.sjb-collection-export');
            if (exportBtn && wrap.contains(exportBtn)) {
                const name = exportBtn.getAttribute('data-collection-name') || '';
                window.alert((i18n.exportTodo || 'Exportar: pendiente de implementar.') + (name ? '\n' + name : ''));
                return;
            }
            const dupBtn = target.closest('.sjb-collection-duplicate');
            if (dupBtn && wrap.contains(dupBtn)) {
                const name = dupBtn.getAttribute('data-collection-name') || '';
                window.alert((i18n.duplicateTodo || 'Duplicar: pendiente de implementar.') + (name ? '\n' + name : ''));
            }
        });
    });

    /**
     * Tabla editable de marcadores (validación + AJAX).
     *
     * @param {HTMLElement} wrap Contenedor admin.
     */
    function initMarkersTable(wrap) {
        const table = wrap.querySelector('#sjb-markers-table');
        const tbody = wrap.querySelector('#sjb-markers-tbody');
        const tpl = wrap.querySelector('#sjb-marker-row-template');
        const addBtn = wrap.querySelector('#sjb-add-marker');
        const emptyEl = wrap.querySelector('#sjb-markers-empty');
        const helpEl = wrap.querySelector('#sjb-markers-help');

        if (!table || !tbody || !tpl || !addBtn) {
            return;
        }

        const collectionId = table.getAttribute('data-collection-id') || '0';
        const collectionIconUrl = table.getAttribute('data-collection-icon-url')
            || cfg.leafletIconUrl
            || '';
        const confirmDelete = table.getAttribute('data-confirm-delete')
            || i18n.markerConfirmDel
            || '¿Eliminar este marcador?';

        /**
         * Deja solo número decimal (signo, dígitos y un separador).
         *
         * @param {string} raw Valor del input.
         * @returns {string}
         */
        function sanitizeCoordValue(raw) {
            let out = '';
            let hasSep = false;
            const str = String(raw);
            for (let i = 0; i < str.length; i++) {
                const ch = str[i];
                if (ch === '-' && out === '') {
                    out += ch;
                    continue;
                }
                if ((ch === '.' || ch === ',') && !hasSep) {
                    out += ch;
                    hasSep = true;
                    continue;
                }
                if (ch >= '0' && ch <= '9') {
                    out += ch;
                }
            }
            return out;
        }

        /**
         * ¿Lat/lng numéricos y en rango?
         *
         * @param {string} latRaw Latitud.
         * @param {string} lngRaw Longitud.
         * @returns {boolean}
         */
        function isValidCoords(latRaw, lngRaw) {
            const latStr = String(latRaw).trim().replace(',', '.');
            const lngStr = String(lngRaw).trim().replace(',', '.');
            if (latStr === '' || lngStr === '' || latStr === '-' || lngStr === '-'
                || latStr === '.' || lngStr === '.' || latStr === '-.' || lngStr === '-.') {
                return false;
            }
            const lat = Number(latStr);
            const lng = Number(lngStr);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return false;
            }
            if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                return false;
            }
            return true;
        }

        /**
         * Valida la fila y aplica estilo danger sutil.
         *
         * @param {HTMLTableRowElement} row Fila.
         * @returns {boolean}
         */
        function validateRow(row) {
            const latEl = row.querySelector('.sjb-marker-lat');
            const lngEl = row.querySelector('.sjb-marker-lng');
            const ok = isValidCoords(latEl ? latEl.value : '', lngEl ? lngEl.value : '');
            row.classList.toggle('sjb-marker-row--invalid', !ok);
            return ok;
        }

        /**
         * Vacío: solo mensaje + botón. Con filas: ayuda + tabla + botón.
         */
        function syncEmptyState() {
            const hasRows = tbody.querySelectorAll('.sjb-marker-row').length > 0;
            table.classList.toggle('d-none', !hasRows);
            if (emptyEl) {
                emptyEl.classList.toggle('d-none', hasRows);
            }
            if (helpEl) {
                helpEl.classList.toggle('d-none', !hasRows);
            }
        }

        /**
         * Índice de orden de la fila.
         *
         * @param {HTMLTableRowElement} row Fila.
         * @returns {number}
         */
        function rowSortOrder(row) {
            return Array.prototype.indexOf.call(tbody.children, row);
        }

        /**
         * Guarda la fila por AJAX si es válida.
         * La validación de lat/lng solo marca la fila; el toast queda para errores I/O.
         *
         * @param {HTMLTableRowElement} row Fila.
         */
        async function saveRow(row) {
            if (row.dataset.saving === '1') {
                return;
            }

            if (!validateRow(row)) {
                return;
            }

            row.dataset.saving = '1';
            row.classList.add('is-sjb-busy');

            const latEl = row.querySelector('.sjb-marker-lat');
            const lngEl = row.querySelector('.sjb-marker-lng');
            const textEl = row.querySelector('.sjb-marker-text');
            const modeEl = row.querySelector('.sjb-marker-mode');

            const result = await ajax('save_marker', {
                collection_id: collectionId,
                marker_id: row.dataset.markerId || '0',
                marker_lat: latEl ? String(latEl.value).trim().replace(',', '.') : '',
                marker_lng: lngEl ? String(lngEl.value).trim().replace(',', '.') : '',
                marker_text: textEl ? textEl.value : '',
                marker_display_mode: modeEl ? modeEl.value : 'both',
                marker_sort_order: String(rowSortOrder(row)),
                marker_is_active: row.dataset.active === '0' ? '0' : '1',
                marker_icon_source: row.dataset.iconSource || 'inherit',
                marker_icon_attachment: row.dataset.iconAttachment || '0',
            });

            row.dataset.saving = '0';
            row.classList.remove('is-sjb-busy');

            if (!result.ok) {
                toast(result.message, 'error');
                return;
            }

            if (result.data.id) {
                row.dataset.markerId = String(result.data.id);
            }

            toast(result.message || i18n.markerSaved || 'Marcador guardado.', 'success');
        }

        /**
         * Miniatura del icono resuelto (colección o propio).
         *
         * @param {HTMLTableRowElement} row Fila.
         */
        function applyMarkerIconThumb(row) {
            const img = row.querySelector('.sjb-marker-icon-btn img');
            const btn = row.querySelector('.sjb-marker-icon-btn');
            if (!img) {
                return;
            }
            const source = row.dataset.iconSource || 'inherit';
            const preview = row.dataset.iconPreview || '';
            const own = source === 'media' && preview;
            img.src = own ? preview : collectionIconUrl;
            if (btn) {
                const label = own
                    ? (i18n.iconOwn || 'Icono propio (clic para cambiar)')
                    : (i18n.iconCollection || 'Icono de la colección (clic para cambiar)');
                btn.setAttribute('title', label);
                btn.setAttribute('aria-label', label);
            }
        }

        /**
         * Programa guardado con debounce.
         *
         * @param {HTMLTableRowElement} row Fila.
         */
        function scheduleSave(row) {
            validateRow(row);
            if (row._sjbSaveTimer) {
                clearTimeout(row._sjbSaveTimer);
            }
            row._sjbSaveTimer = setTimeout(() => {
                saveRow(row);
            }, 550);
        }

        /**
         * Crea una fila desde el template.
         *
         * @returns {HTMLTableRowElement|null}
         */
        function createRowFromTemplate() {
            const node = tpl.content.cloneNode(true);
            const row = node.querySelector('.sjb-marker-row');
            return row;
        }

        /**
         * Añade fila vacía al final.
         */
        function addEmptyRow() {
            const row = createRowFromTemplate();
            if (!row) {
                return;
            }
            tbody.appendChild(row);
            applyMarkerIconThumb(row);
            validateRow(row);
            syncEmptyState();
            const latEl = row.querySelector('.sjb-marker-lat');
            if (latEl) {
                latEl.focus();
            }
        }

        /**
         * Duplica una fila (nueva sin ID; se guarda al validar).
         *
         * @param {HTMLTableRowElement} source Fila origen.
         */
        function duplicateRow(source) {
            const row = createRowFromTemplate();
            if (!row) {
                return;
            }

            const srcLat = source.querySelector('.sjb-marker-lat');
            const srcLng = source.querySelector('.sjb-marker-lng');
            const srcText = source.querySelector('.sjb-marker-text');
            const srcMode = source.querySelector('.sjb-marker-mode');

            const latEl = row.querySelector('.sjb-marker-lat');
            const lngEl = row.querySelector('.sjb-marker-lng');
            const textEl = row.querySelector('.sjb-marker-text');
            const modeEl = row.querySelector('.sjb-marker-mode');

            if (latEl && srcLat) {
                latEl.value = srcLat.value;
            }
            if (lngEl && srcLng) {
                lngEl.value = srcLng.value;
            }
            if (textEl && srcText) {
                textEl.value = srcText.value;
            }
            if (modeEl && srcMode) {
                modeEl.value = srcMode.value;
            }

            applyStatusUi(row, source.dataset.active !== '0');
            row.dataset.markerId = '0';
            row.dataset.iconSource = source.dataset.iconSource || 'inherit';
            row.dataset.iconAttachment = source.dataset.iconAttachment || '0';
            row.dataset.iconPreview = source.dataset.iconPreview || '';
            applyMarkerIconThumb(row);
            source.after(row);
            validateRow(row);
            syncEmptyState();
            saveRow(row);
        }

        /**
         * Pinta el icono de estado activo/inactivo.
         *
         * @param {HTMLTableRowElement} row Fila.
         * @param {boolean} active Estado.
         */
        function applyStatusUi(row, active) {
            row.dataset.active = active ? '1' : '0';
            const btn = row.querySelector('.sjb-marker-status');
            const icon = btn ? btn.querySelector('.dashicons') : null;
            if (!btn || !icon) {
                return;
            }
            btn.classList.toggle('sjb-icon-btn--active', active);
            btn.classList.toggle('sjb-icon-btn--inactive', !active);
            icon.classList.toggle('dashicons-yes-alt', active);
            icon.classList.toggle('dashicons-dismiss', !active);
            const label = active
                ? (i18n.markerActive || 'Activo (clic para desactivar)')
                : (i18n.markerInactive || 'Inactivo (clic para activar)');
            btn.setAttribute('title', label);
            btn.setAttribute('aria-label', label);
        }

        /**
         * Alterna estado (AJAX si ya está guardado; local si es fila nueva).
         *
         * @param {HTMLTableRowElement} row Fila.
         */
        async function toggleStatus(row) {
            const markerId = parseInt(row.dataset.markerId || '0', 10);
            const nextActive = row.dataset.active === '0';

            if (markerId < 1) {
                applyStatusUi(row, nextActive);
                return;
            }

            row.classList.add('is-sjb-busy');
            const result = await ajax('toggle_marker_active', {
                marker_id: String(markerId),
                collection_id: collectionId,
            });
            row.classList.remove('is-sjb-busy');

            if (!result.ok) {
                toast(result.message, 'error');
                return;
            }

            applyStatusUi(row, Number(result.data.is_active) === 1);
            toast(result.message, 'success');
        }

        /**
         * Elimina marcador (BD si tiene ID; si no, solo DOM).
         *
         * @param {HTMLTableRowElement} row Fila.
         */
        async function deleteRow(row) {
            if (!window.confirm(confirmDelete)) {
                return;
            }

            const markerId = parseInt(row.dataset.markerId || '0', 10);
            if (markerId > 0) {
                row.classList.add('is-sjb-busy');
                const result = await ajax('delete_marker', {
                    marker_id: String(markerId),
                    collection_id: collectionId,
                });
                row.classList.remove('is-sjb-busy');
                if (!result.ok) {
                    toast(result.message, 'error');
                    return;
                }
                toast(result.message || i18n.markerDeleted || 'Marcador eliminado.', 'success');
            }

            if (row._sjbSaveTimer) {
                clearTimeout(row._sjbSaveTimer);
            }
            row.remove();
            syncEmptyState();
        }

        const iconModalEl = document.getElementById('sjb-modal-marker-icon');
        const iconForm = document.getElementById('sjb-form-marker-icon');
        let iconRow = null;

        addBtn.addEventListener('click', () => addEmptyRow());

        tbody.addEventListener('input', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            const row = target.closest('.sjb-marker-row');
            if (!row || !tbody.contains(row)) {
                return;
            }
            if (target.matches('.sjb-marker-lat, .sjb-marker-lng')) {
                if (target instanceof HTMLInputElement) {
                    const before = target.value;
                    const sanitized = sanitizeCoordValue(before);
                    if (sanitized !== before) {
                        const pos = target.selectionStart || 0;
                        const diff = before.length - sanitized.length;
                        target.value = sanitized;
                        const caret = Math.max(0, pos - diff);
                        target.setSelectionRange(caret, caret);
                    }
                }
                scheduleSave(row);
                return;
            }
            if (target.matches('.sjb-marker-text')) {
                scheduleSave(row);
            }
        });

        tbody.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            const row = target.closest('.sjb-marker-row');
            if (!row || !tbody.contains(row)) {
                return;
            }
            if (target.matches('.sjb-marker-mode')) {
                scheduleSave(row);
            }
        });

        tbody.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }
            const row = target.closest('.sjb-marker-row');
            if (!row || !tbody.contains(row)) {
                return;
            }
            if (target.closest('.sjb-marker-icon-btn')) {
                const picker = iconForm ? iconForm.querySelector('[data-sjb-icon-picker]') : null;
                iconRow = row;
                if (picker) {
                    setIconPickerState(picker, {
                        source: row.dataset.iconSource || 'inherit',
                        attachmentId: row.dataset.iconAttachment || '0',
                        previewUrl: row.dataset.iconPreview || '',
                    });
                }
                if (iconModalEl && window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(iconModalEl).show();
                }
                return;
            }
            if (target.closest('.sjb-marker-status')) {
                toggleStatus(row);
                return;
            }
            if (target.closest('.sjb-marker-duplicate')) {
                duplicateRow(row);
                return;
            }
            if (target.closest('.sjb-marker-delete')) {
                deleteRow(row);
            }
        });

        if (iconForm instanceof HTMLFormElement) {
            iconForm.addEventListener('submit', (event) => {
                event.preventDefault();
                if (!iconRow) {
                    return;
                }
                const picker = iconForm.querySelector('[data-sjb-icon-picker]');
                const sourceRadio = picker ? picker.querySelector('.sjb-icon-source:checked') : null;
                let source = sourceRadio ? sourceRadio.value : 'inherit';
                const idInput = picker ? picker.querySelector('.sjb-icon-attachment-id') : null;
                const previewImg = picker ? picker.querySelector('[data-sjb-icon-preview] img') : null;
                let attachment = idInput ? String(idInput.value || '0') : '0';
                let preview = previewImg ? (previewImg.getAttribute('src') || '') : '';
                if (source !== 'media' || attachment === '0' || preview === '') {
                    source = 'inherit';
                    attachment = '0';
                    preview = '';
                }
                iconRow.dataset.iconSource = source;
                iconRow.dataset.iconAttachment = attachment;
                iconRow.dataset.iconPreview = preview;
                applyMarkerIconThumb(iconRow);
                if (iconModalEl && window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(iconModalEl).hide();
                }
                saveRow(iconRow);
            });
        }

        tbody.querySelectorAll('.sjb-marker-row').forEach((row) => validateRow(row));
        syncEmptyState();
    }
})();
