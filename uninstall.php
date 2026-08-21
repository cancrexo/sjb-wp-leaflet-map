<?php
/**
 * Desinstalación del plugin.
 *
 * Si el switch "Borrar datos al desinstalar" está activo, se eliminan las
 * opciones. Si está desactivado, los datos se conservan.
 *
 * @package sjb-wp-leaflet-map
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$option_name   = 'sjb_wp_leaflet_map_options';
$legacy_option = 'sjb_wp_leafleet_map_options';
$options       = get_option( $option_name, array() );

if ( is_array( $options ) && ! empty( $options['delete_onuninstall'] ) ) {
    delete_option( $option_name );
    delete_option( $legacy_option );
}
