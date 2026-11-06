/**
 * Bloques Gutenberg de los mapas Leaflet.
 * En el editor se muestra una ficha; el HTML del mapa lo genera PHP.
 */
(() => {
    'use strict';

    const { registerBlockType, getBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, TextControl, TextareaControl, SelectControl, ToggleControl } = wp.components;
    const { createElement: el, Fragment } = wp.element;
    const { __, sprintf } = wp.i18n;

    if (getBlockType('sjb-wp-leaflet-map/map')) {
        return;
    }

    const collections = (window.sjbLeafletMapBlocks && window.sjbLeafletMapBlocks.collections) || [
        { label: __('— Seleccionar —', 'sjb-wp-leaflet-map'), value: '' },
    ];

    /**
     * Campo de texto de la barra lateral.
     *
     * @param {string} label Etiqueta.
     * @param {string} attribute Nombre del atributo.
     * @param {Object} attributes Atributos del bloque.
     * @param {Function} setAttributes Setter.
     * @param {Object} extra Props extra de TextControl.
     */
    const textField = (label, attribute, attributes, setAttributes, extra = {}) => el(TextControl, {
        label,
        value: attributes[attribute],
        onChange: (value) => setAttributes({ [attribute]: value == null ? '' : String(value) }),
        ...extra,
    });

    /**
     * Ficha visible en el lienzo del editor.
     *
     * @param {Object} blockProps Props de useBlockProps.
     * @param {string} title Título.
     * @param {string[]} lines Líneas de resumen.
     */
    const card = (blockProps, title, lines) => el(
        'div',
        blockProps,
        el('p', { className: 'sjb-leaflet-block-preview__title' }, title),
        lines.map((line, index) => el(
            'p',
            { key: index, className: 'sjb-leaflet-block-preview__line' },
            line
        )),
        el(
            'p',
            { className: 'sjb-leaflet-block-preview__hint' },
            __('El mapa se muestra al ver la página.', 'sjb-wp-leaflet-map')
        )
    );

    /**
     * Centro, zoom y tamaño, comunes a los dos bloques.
     *
     * @param {Object} attributes Atributos.
     */
    const summaryLines = (attributes) => {
        const lines = [
            sprintf(
                /* translators: 1: latitud, 2: longitud, 3: zoom */
                __('Centro: %1$s, %2$s · zoom %3$s', 'sjb-wp-leaflet-map'),
                attributes.lat,
                attributes.lng,
                attributes.zoom
            ),
            sprintf(
                /* translators: 1: ancho, 2: alto */
                __('Tamaño: %1$s × %2$s', 'sjb-wp-leaflet-map'),
                attributes.width,
                attributes.height
            ),
        ];

        if (attributes.zoom_needs_ctrlkey) {
            lines.push(__('Zoom con la rueda: solo con Ctrl.', 'sjb-wp-leaflet-map'));
        }

        return lines;
    };

    /**
     * Campos de mapa compartidos (centro, zoom, tamaño e id).
     *
     * @param {Object} attributes Atributos.
     * @param {Function} setAttributes Setter.
     */
    const mapFields = (attributes, setAttributes) => el(
        Fragment,
        null,
        textField(__('Latitud', 'sjb-wp-leaflet-map'), 'lat', attributes, setAttributes),
        textField(__('Longitud', 'sjb-wp-leaflet-map'), 'lng', attributes, setAttributes),
        textField(__('Zoom', 'sjb-wp-leaflet-map'), 'zoom', attributes, setAttributes, {
            type: 'number',
            min: 0,
            max: 19,
            step: 1,
            help: __('Nivel de zoom Leaflet (0–19).', 'sjb-wp-leaflet-map'),
        }),
        textField(__('Ancho', 'sjb-wp-leaflet-map'), 'width', attributes, setAttributes, {
            help: __('Ej. 100% o 600 (px).', 'sjb-wp-leaflet-map'),
        }),
        textField(__('Alto', 'sjb-wp-leaflet-map'), 'height', attributes, setAttributes, {
            help: __('Ej. 400px o 400.', 'sjb-wp-leaflet-map'),
        }),
        textField(__('ID del contenedor', 'sjb-wp-leaflet-map'), 'id', attributes, setAttributes, {
            help: __('Se sanitiza a un id HTML válido.', 'sjb-wp-leaflet-map'),
        }),
        el(ToggleControl, {
            label: __('Zoom con la rueda solo con Ctrl', 'sjb-wp-leaflet-map'),
            help: __('Sin la tecla, la rueda desplaza la página. En Mac también vale Comando. Los botones + y − siguen igual.', 'sjb-wp-leaflet-map'),
            checked: !!attributes.zoom_needs_ctrlkey,
            onChange: (value) => setAttributes({ zoom_needs_ctrlkey: !!value }),
        })
    );

    registerBlockType('sjb-wp-leaflet-map/map', {
        edit: ({ attributes, setAttributes }) => {
            const blockProps = useBlockProps({ className: 'sjb-leaflet-block-preview' });
            const lines = summaryLines(attributes);

            if (attributes.marker_lat !== '' || attributes.marker_lng !== '') {
                lines.push(sprintf(
                    /* translators: 1: latitud del marcador, 2: longitud del marcador */
                    __('Marcador: %1$s, %2$s', 'sjb-wp-leaflet-map'),
                    attributes.marker_lat,
                    attributes.marker_lng
                ));
            }

            return el(
                Fragment,
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __('Mapa', 'sjb-wp-leaflet-map'), initialOpen: true },
                        mapFields(attributes, setAttributes)
                    ),
                    el(
                        PanelBody,
                        { title: __('Marcador', 'sjb-wp-leaflet-map'), initialOpen: false },
                        textField(__('Latitud del marcador', 'sjb-wp-leaflet-map'), 'marker_lat', attributes, setAttributes, {
                            help: __('Vacío = sin marcador.', 'sjb-wp-leaflet-map'),
                        }),
                        textField(__('Longitud del marcador', 'sjb-wp-leaflet-map'), 'marker_lng', attributes, setAttributes),
                        el(TextareaControl, {
                            label: __('Texto del marcador', 'sjb-wp-leaflet-map'),
                            help: __('Se muestra al pasar el ratón y al hacer clic.', 'sjb-wp-leaflet-map'),
                            value: attributes.marker_text,
                            onChange: (value) => setAttributes({ marker_text: value == null ? '' : String(value) }),
                        })
                    )
                ),
                card(blockProps, __('SJB Leaflet Map', 'sjb-wp-leaflet-map'), lines)
            );
        },
        save: () => null,
    });

    /**
     * Opciones del desplegable, conservando un slug que ya no exista.
     *
     * @param {string} current Slug guardado en el bloque.
     */
    const collectionOptions = (current) => {
        const options = collections.map((item) => ({
            label: item.label,
            value: item.value,
        }));

        if (current && !options.some((item) => item.value === current)) {
            options.push({ label: current, value: current });
        }

        return options;
    };

    /**
     * Etiqueta visible de la colección elegida.
     *
     * @param {string} slug Slug o vacío.
     */
    const collectionLabel = (slug) => {
        if (!slug) {
            return __('Sin colección', 'sjb-wp-leaflet-map');
        }

        const found = collections.find((item) => item.value === slug);
        return found ? found.label : slug;
    };

    registerBlockType('sjb-wp-leaflet-map/collection', {
        edit: ({ attributes, setAttributes }) => {
            const blockProps = useBlockProps({ className: 'sjb-leaflet-block-preview' });
            const lines = summaryLines(attributes);

            lines.push(sprintf(
                /* translators: %s: nombre de la colección */
                __('Colección: %s', 'sjb-wp-leaflet-map'),
                collectionLabel(attributes.collection)
            ));

            return el(
                Fragment,
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __('Mapa', 'sjb-wp-leaflet-map'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Colección', 'sjb-wp-leaflet-map'),
                            value: attributes.collection,
                            options: collectionOptions(attributes.collection),
                            help: __('Se muestran solo los marcadores activos.', 'sjb-wp-leaflet-map'),
                            onChange: (value) => setAttributes({ collection: value == null ? '' : String(value) }),
                        }),
                        mapFields(attributes, setAttributes)
                    )
                ),
                card(blockProps, __('SJB Leaflet Collection', 'sjb-wp-leaflet-map'), lines)
            );
        },
        save: () => null,
    });
})();
