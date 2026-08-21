<?php
/**
 * Pestaña Configuración.
 *
 * @package sjb-wp-leafleet-map
 *
 * @var array<string,mixed> $options
 */

defined( 'ABSPATH' ) || exit;
?>
<form method="post" action="">
    <?php wp_nonce_field( 'sjb_wp_leafleet_map_save_settings' ); ?>

    <p class="text-muted">
        <?php esc_html_e( 'Ajustes generales del plugin. Más opciones se añadirán en próximas versiones.', 'sjb-wp-leafleet-map' ); ?>
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
            <?php esc_html_e( 'Borrar los datos del plugin al desinstalar', 'sjb-wp-leafleet-map' ); ?>
        </label>
    </div>

    <p class="form-text">
        <?php esc_html_e( 'Si está activo, al desinstalar se eliminarán las opciones y datos del plugin. Si está desactivado, se conservarán.', 'sjb-wp-leafleet-map' ); ?>
    </p>

    <button type="submit" name="sjb_wp_leafleet_map_save" value="1" class="btn btn-primary">
        <?php esc_html_e( 'Guardar cambios', 'sjb-wp-leafleet-map' ); ?>
    </button>
</form>
