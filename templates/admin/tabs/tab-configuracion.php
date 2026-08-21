<?php
/**
 * Pestaña Configuración.
 *
 * @package sjb-wp-leaflet-map
 *
 * @var array<string,mixed> $options
 */

defined( 'ABSPATH' ) || exit;
?>
<form method="post" action="" class="sjb-ajax-form" data-sjb-action="save_settings">
    <p class="text-muted">
        <?php esc_html_e( 'Ajustes generales del plugin. Más opciones se añadirán en próximas versiones.', 'sjb-wp-leaflet-map' ); ?>
    </p>

    <div class="form-check form-switch mb-3">
        <input
            class="form-check-input"
            type="checkbox"
            role="switch"
            id="delete_onuninstall"
            name="delete_onuninstall"
            value="1"
            <?php checked( ! empty( $options['delete_onuninstall'] ) ); ?>
        >
        <label class="form-check-label" for="delete_onuninstall">
            <?php esc_html_e( 'Borrar los datos del plugin al desinstalar', 'sjb-wp-leaflet-map' ); ?>
        </label>
    </div>

    <p class="form-text">
        <?php esc_html_e( 'Si está activo, al desinstalar se eliminarán las opciones y datos del plugin. Si está desactivado, se conservarán.', 'sjb-wp-leaflet-map' ); ?>
    </p>

    <button type="submit" class="btn btn-primary">
        <?php esc_html_e( 'Guardar cambios', 'sjb-wp-leaflet-map' ); ?>
    </button>
</form>
