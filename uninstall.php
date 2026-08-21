<?php
/**
 * Desinstalación del plugin.
 *
 * Si el switch "Borrar datos al desinstalar" está activo, se eliminan las
 * opciones y las tablas de colecciones/marcadores.
 *
 * @package sjb-wp-leaflet-map
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$option_name   = 'sjb_wp_leaflet_map_options';
$legacy_option = 'sjb_wp_leafleet_map_options';
$options       = get_option( $option_name, array() );

if ( is_array( $options ) && ! empty( $options['delete_onuninstall'] ) ) {
    global $wpdb;

    $wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->prefix . 'sjb_wp_leaflet_map_markers`' );
    $wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->prefix . 'sjb_wp_leaflet_map_collections`' );

    delete_option( $option_name );
    delete_option( $legacy_option );
}
