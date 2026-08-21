<?php
/**
 * Pestaña Info.
 *
 * @package sjb-wp-leaflet-map
 *
 */

defined( 'ABSPATH' ) || exit;
?>
<h2 class="h4"><?php esc_html_e( 'Información', 'sjb-wp-leaflet-map' ); ?></h2>
<p class="text-muted">
    <?php esc_html_e( 'Información del plugin, créditos y ayuda. Contenido pendiente.', 'sjb-wp-leaflet-map' ); ?>
</p>
<p>
    <strong><?php esc_html_e( 'Versión:', 'sjb-wp-leaflet-map' ); ?></strong>
    <?php echo esc_html( SJB_WP_LEAFLET_MAP::$version ); ?>
</p>
