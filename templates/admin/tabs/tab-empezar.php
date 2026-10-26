<?php
/**
 * Pestaña Antes de empezar: los dos shortcodes del plugin.
 *
 * @package sjb-wp-leaflet-map
 */

defined( 'ABSPATH' ) || exit;
?>
<h2 class="h4"><?php esc_html_e( 'Antes de empezar', 'sjb-wp-leaflet-map' ); ?></h2>
<p class="text-muted">
    <?php esc_html_e( 'Hay dos shortcodes. Cada uno hace una cosa distinta y no son intercambiables.', 'sjb-wp-leaflet-map' ); ?>
</p>

<div class="sjb-config-cards">
    <div class="card">
        <div class="card-header">
            <?php esc_html_e( 'SJB Leaflet Map', 'sjb-wp-leaflet-map' ); ?>
        </div>
        <div class="card-body">
            <p><code>[sjb_leaflet_map]</code></p>
            <p class="mb-0">
                <?php esc_html_e( 'Mapa sencillo. En el shortcode se indican a mano la latitud y la longitud del centro (lat y lng) y, si se quiere, el zoom. El pin es opcional: marker_lat y marker_lng colocan un único marcador, y marker_text es su texto. Las colecciones de este plugin no se usan aquí.', 'sjb-wp-leaflet-map' ); ?>
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <?php esc_html_e( 'SJB Leaflet Collection', 'sjb-wp-leaflet-map' ); ?>
        </div>
        <div class="card-body">
            <p><code>[sjb_leaflet_collection]</code></p>
            <p class="mb-0">
                <?php esc_html_e( 'En el shortcode se fija el centro del mapa (lat, lng y zoom) y se indica la colección con el atributo collection (slug o ID). El mapa muestra los marcadores activos de esa colección. Las colecciones de la pestaña Marcadores solo funcionan con este shortcode.', 'sjb-wp-leaflet-map' ); ?>
            </p>
        </div>
    </div>
</div>

<p class="text-muted mt-3">
    <?php esc_html_e( 'Si WPBakery está activo, los mismos shortcodes aparecen en SJB Shortcodes como SJB Leaflet Map y SJB Leaflet Collection.', 'sjb-wp-leaflet-map' ); ?>
</p>
