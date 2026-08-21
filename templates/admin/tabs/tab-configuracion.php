<?php
/**
 * Pestaña Configuración.
 *
 * @package sjb-wp-leaflet-map
 *
 * @var array<string,mixed> $options
 */

defined( 'ABSPATH' ) || exit;

$icon_source = isset( $options['marker_icon_source'] ) ? (string) $options['marker_icon_source'] : 'leaflet';
if ( ! in_array( $icon_source, array( 'leaflet', 'media' ), true ) ) {
    $icon_source = 'leaflet';
}
$icon_attachment = isset( $options['marker_icon_attachment'] ) ? absint( $options['marker_icon_attachment'] ) : 0;
$icon_preview    = $icon_attachment ? wp_get_attachment_image_url( $icon_attachment, 'thumbnail' ) : '';
$leaflet_icon    = SJB_WP_LEAFLET_MAP::$path2assets . 'vendor/leaflet/images/marker-icon.png';
?>
<form method="post" action="" class="sjb-ajax-form" data-sjb-action="save_settings">

    <div class="sjb-config-cards">
        <div class="card">
            <div class="card-header">
                <?php esc_html_e( 'Icono de marcador por defecto', 'sjb-wp-leaflet-map' ); ?>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    <?php esc_html_e( 'Se usa en todos los mapas y colecciones, salvo que una colección defina el suyo.', 'sjb-wp-leaflet-map' ); ?>
                </p>

                <div class="sjb-icon-picker" data-sjb-icon-picker>
                    <div class="mb-3">
                        <div class="form-check">
                            <input
                                class="form-check-input sjb-icon-source"
                                type="radio"
                                name="marker_icon_source"
                                id="marker_icon_source_leaflet"
                                value="leaflet"
                                <?php checked( $icon_source, 'leaflet' ); ?>
                            >
                            <label class="form-check-label" for="marker_icon_source_leaflet">
                                <?php esc_html_e( 'Icono de Leaflet (por defecto)', 'sjb-wp-leaflet-map' ); ?>
                            </label>
                        </div>
                        <div class="form-check">
                            <input
                                class="form-check-input sjb-icon-source"
                                type="radio"
                                name="marker_icon_source"
                                id="marker_icon_source_media"
                                value="media"
                                <?php checked( $icon_source, 'media' ); ?>
                            >
                            <label class="form-check-label" for="marker_icon_source_media">
                                <?php esc_html_e( 'Imagen de la biblioteca multimedia', 'sjb-wp-leaflet-map' ); ?>
                            </label>
                        </div>
                    </div>

                    <div class="sjb-icon-media-row mb-0<?php echo 'media' === $icon_source ? '' : ' d-none'; ?>" data-sjb-icon-media-row>
                        <input type="hidden" name="marker_icon_attachment" class="sjb-icon-attachment-id" value="<?php echo esc_attr( (string) $icon_attachment ); ?>">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="sjb-icon-preview" data-sjb-icon-preview>
                                <?php if ( $icon_preview ) : ?>
                                    <img src="<?php echo esc_url( $icon_preview ); ?>" alt="">
                                <?php else : ?>
                                    <span class="text-muted small"><?php esc_html_e( 'Ninguna imagen seleccionada', 'sjb-wp-leaflet-map' ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm sjb-icon-select">
                                    <?php echo $icon_attachment ? esc_html__( 'Cambiar imagen', 'sjb-wp-leaflet-map' ) : esc_html__( 'Seleccionar imagen', 'sjb-wp-leaflet-map' ); ?>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm sjb-icon-clear<?php echo $icon_attachment ? '' : ' d-none'; ?>">
                                    <?php esc_html_e( 'Quitar', 'sjb-wp-leaflet-map' ); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="sjb-icon-leaflet-preview<?php echo 'leaflet' === $icon_source ? '' : ' d-none'; ?>" data-sjb-icon-leaflet-preview>
                        <img src="<?php echo esc_url( $leaflet_icon ); ?>" alt="" width="25" height="41">
                        <span class="text-muted small ms-2"><?php esc_html_e( 'Vista previa del pin de Leaflet', 'sjb-wp-leaflet-map' ); ?></span>
                    </div>
                </div>

                <?php
                $icon_size = isset( $options['marker_icon_size'] ) ? absint( $options['marker_icon_size'] ) : 48;
                if ( $icon_size < 16 ) {
                    $icon_size = 16;
                }
                if ( $icon_size > 128 ) {
                    $icon_size = 128;
                }
                ?>
                <div class="mt-3">
                    <label class="form-label" for="marker_icon_size"><?php esc_html_e( 'Tamaño del icono en el mapa (px)', 'sjb-wp-leaflet-map' ); ?></label>
                    <input
                        class="form-control"
                        type="number"
                        name="marker_icon_size"
                        id="marker_icon_size"
                        min="16"
                        max="128"
                        step="1"
                        value="<?php echo esc_attr( (string) $icon_size ); ?>"
                        style="max-width: 8rem;"
                    >
                    <p class="form-text mb-0">
                        <?php esc_html_e( 'Lado máximo de la miniatura (16–128). WordPress genera un tamaño propio, sin recortar, y el mapa usa esa imagen en lugar del original. Por defecto 48.', 'sjb-wp-leaflet-map' ); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <?php esc_html_e( 'Ajustes generales', 'sjb-wp-leaflet-map' ); ?>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-2">
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
                <p class="form-text mb-0">
                    <?php esc_html_e( 'Si está activo, al desinstalar se eliminarán las opciones y datos del plugin. Si está desactivado, se conservarán.', 'sjb-wp-leaflet-map' ); ?>
                </p>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <?php esc_html_e( 'Guardar cambios', 'sjb-wp-leaflet-map' ); ?>
    </button>
</form>
