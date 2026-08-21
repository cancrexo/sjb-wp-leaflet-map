<?php
/**
 * Pestaña Info.
 *
 * @package sjb-wp-leafleet-map
 *
 */

defined( 'ABSPATH' ) || exit;
?>
<h2 class="h4"><?php esc_html_e( 'Información', 'sjb-wp-leafleet-map' ); ?></h2>
<p class="text-muted">
    <?php esc_html_e( 'Información del plugin, créditos y ayuda. Contenido pendiente.', 'sjb-wp-leafleet-map' ); ?>
</p>
<p>
    <strong><?php esc_html_e( 'Versión:', 'sjb-wp-leafleet-map' ); ?></strong>
    <?php echo esc_html( SJB_WP_LEAFLEET_MAP::$version ); ?>
</p>
