<?php
/**
 * Shortcodes públicos, assets Leaflet y mapeo WPBakery.
 *
 * @package sjb-wp-leaflet-map
 * @author  Daniel "Cancrexo" Prol
 * @email   cancrexo@gmail.com
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registro de shortcodes y su utilería (frontend + WPBakery).
 */
class SJB_WP_LEAFLET_MAP_Shortcodes {

    /**
     * Zoom mínimo Leaflet (mapa / tiles OSM).
     */
    private const ZOOM_MIN = 0;

    /**
     * Zoom máximo alineado con maxZoom de la capa OSM (Leaflet).
     */
    private const ZOOM_MAX = 19;

    /**
     * Flag para imprimir assets públicos solo si hay shortcode en la página.
     *
     * @var int
     */
    public static $add_script = 0;

    /**
     * Hooks de shortcodes, assets y WPBakery.
     */
    public static function register(): void {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_public_scripts' ) );
        add_action( 'wp_footer', array( __CLASS__, 'print_public_scripts' ) );
        add_shortcode( 'sjb_leaflet_map', array( __CLASS__, 'shortcode_leaflet_map' ) );
        add_shortcode( 'sjb_leaflet_collection', array( __CLASS__, 'shortcode_leaflet_collection' ) );

        // Shortcodes en WPBakery (solo si el plugin está activo).
        if ( function_exists( 'vc_map' ) ) {
            add_action( 'vc_before_init', array( __CLASS__, 'map_vc_shortcodes' ) );
        }
    }

    /**
     * Mapea shortcodes en WPBakery Page Builder.
     *
     * Importante: WPBakery exige al menos un parámetro en el mapeo y que el
     * shortcode acepte atributos. Si un shortcode futuro no necesita opciones,
     * hay que forzar un param (p. ej. textfield el_class) aunque no se use.
     */
    public static function map_vc_shortcodes(): void {
        if ( ! function_exists( 'vc_map' ) ) {
            return;
        }

        // Param número propio: el tipo nativo «range» de WPBakery ignora max y usa 100.
        if ( function_exists( 'vc_add_shortcode_param' ) ) {
            vc_add_shortcode_param( 'sjb_number', array( __CLASS__, 'vc_sjb_number_form_field' ) );
        }

        $category = __( 'SJB Shortcodes', 'sjb-wp-leaflet-map' );

        $shortcodes = array(
            array(
                'base'        => 'sjb_leaflet_map',
                'name'        => __( 'SJB Leaflet Map', 'sjb-wp-leaflet-map' ),
                'description' => __( 'Mapa interactivo Leaflet (OpenStreetMap).', 'sjb-wp-leaflet-map' ),
                'params'      => array(
                    array(
                        'type'             => 'textfield',
                        'heading'          => __( 'Latitud', 'sjb-wp-leaflet-map' ),
                        'param_name'       => 'lat',
                        'value'            => '42.4034506',
                        'admin_label'      => true,
                        'edit_field_class' => 'vc_col-sm-4',
                    ),
                    array(
                        'type'             => 'textfield',
                        'heading'          => __( 'Longitud', 'sjb-wp-leaflet-map' ),
                        'param_name'       => 'lng',
                        'value'            => '-8.8091448',
                        'admin_label'      => true,
                        'edit_field_class' => 'vc_col-sm-4',
                    ),
                    self::get_vc_zoom_param(),
                    array(
                        'type'             => 'textfield',
                        'heading'          => __( 'Ancho', 'sjb-wp-leaflet-map' ),
                        'param_name'       => 'width',
                        'value'            => '100%',
                        'description'      => __( 'Ej. 100% o 600 (px).', 'sjb-wp-leaflet-map' ),
                        'edit_field_class' => 'vc_col-sm-6',
                    ),
                    array(
                        'type'             => 'textfield',
                        'heading'          => __( 'Alto', 'sjb-wp-leaflet-map' ),
                        'param_name'       => 'height',
                        'value'            => '400px',
                        'description'      => __( 'Ej. 400px o 400.', 'sjb-wp-leaflet-map' ),
                        'edit_field_class' => 'vc_col-sm-6',
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => __( 'ID del contenedor', 'sjb-wp-leaflet-map' ),
                        'param_name'  => 'id',
                        'value'       => 'Leaflet Map',
                        'description' => __( 'Se sanitiza a un id HTML válido.', 'sjb-wp-leaflet-map' ),
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => __( 'Latitud del marcador', 'sjb-wp-leaflet-map' ),
                        'param_name'  => 'marker_lat',
                        'value'       => '',
                        'description' => __( 'Vacío = sin marcador.', 'sjb-wp-leaflet-map' ),
                        'group'       => __( 'Marcador', 'sjb-wp-leaflet-map' ),
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => __( 'Longitud del marcador', 'sjb-wp-leaflet-map' ),
                        'param_name'  => 'marker_lng',
                        'value'       => '',
                        'group'       => __( 'Marcador', 'sjb-wp-leaflet-map' ),
                    ),
                    array(
                        'type'        => 'textarea',
                        'heading'     => __( 'Texto del marcador', 'sjb-wp-leaflet-map' ),
                        'param_name'  => 'marker_text',
                        'value'       => '',
                        'description' => __( 'Se muestra al pasar el ratón y al hacer clic.', 'sjb-wp-leaflet-map' ),
                        'group'       => __( 'Marcador', 'sjb-wp-leaflet-map' ),
                    ),
                ),
            ),
            array(
                'base'        => 'sjb_leaflet_collection',
                'name'        => __( 'SJB Leaflet Collection', 'sjb-wp-leaflet-map' ),
                'description' => __( 'Mapa Leaflet con marcadores de una colección.', 'sjb-wp-leaflet-map' ),
                'params'      => array(
                    array(
                        'type'        => 'dropdown',
                        'heading'     => __( 'Colección', 'sjb-wp-leaflet-map' ),
                        'param_name'  => 'collection',
                        'value'       => self::get_vc_collection_choices(),
                        'admin_label' => true,
                        'description' => __( 'Se muestran solo los marcadores activos.', 'sjb-wp-leaflet-map' ),
                    ),
                    array(
                        'type'             => 'textfield',
                        'heading'          => __( 'Latitud', 'sjb-wp-leaflet-map' ),
                        'param_name'       => 'lat',
                        'value'            => '42.4034506',
                        'admin_label'      => true,
                        'edit_field_class' => 'vc_col-sm-4',
                    ),
                    array(
                        'type'             => 'textfield',
                        'heading'          => __( 'Longitud', 'sjb-wp-leaflet-map' ),
                        'param_name'       => 'lng',
                        'value'            => '-8.8091448',
                        'admin_label'      => true,
                        'edit_field_class' => 'vc_col-sm-4',
                    ),
                    self::get_vc_zoom_param(),
                    array(
                        'type'             => 'textfield',
                        'heading'          => __( 'Ancho', 'sjb-wp-leaflet-map' ),
                        'param_name'       => 'width',
                        'value'            => '100%',
                        'description'      => __( 'Ej. 100% o 600 (px).', 'sjb-wp-leaflet-map' ),
                        'edit_field_class' => 'vc_col-sm-6',
                    ),
                    array(
                        'type'             => 'textfield',
                        'heading'          => __( 'Alto', 'sjb-wp-leaflet-map' ),
                        'param_name'       => 'height',
                        'value'            => '400px',
                        'description'      => __( 'Ej. 400px o 400.', 'sjb-wp-leaflet-map' ),
                        'edit_field_class' => 'vc_col-sm-6',
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => __( 'ID del contenedor', 'sjb-wp-leaflet-map' ),
                        'param_name'  => 'id',
                        'value'       => 'Leaflet Collection',
                        'description' => __( 'Se sanitiza a un id HTML válido.', 'sjb-wp-leaflet-map' ),
                    ),
                ),
            ),
        );

        foreach ( $shortcodes as $shortcode ) {
            $shortcode['category'] = $category;
            vc_map( $shortcode );
        }
    }

    /**
     * Opciones de colección para el dropdown de WPBakery (Label => value).
     *
     * @return array<string, string>
     */
    private static function get_vc_collection_choices(): array {
        $choices = array(
            __( '— Seleccionar —', 'sjb-wp-leaflet-map' ) => '',
        );

        if ( ! class_exists( 'SJB_WP_LEAFLET_MAP_Collections' ) ) {
            return $choices;
        }

        foreach ( SJB_WP_LEAFLET_MAP_Collections::get_collections() as $c ) {
            $label             = $c->name . ' (' . $c->slug . ')';
            $choices[ $label ] = $c->slug;
        }

        return $choices;
    }

    /**
     * Parámetro Zoom para WPBakery (input number 0–19).
     *
     * @return array<string, mixed>
     */
    private static function get_vc_zoom_param(): array {
        return array(
            'type'             => 'sjb_number',
            'heading'          => __( 'Zoom', 'sjb-wp-leaflet-map' ),
            'param_name'       => 'zoom',
            'value'            => '13',
            'min'              => self::ZOOM_MIN,
            'max'              => self::ZOOM_MAX,
            'step'             => 1,
            'edit_field_class' => 'vc_col-sm-4',
            'description'      => sprintf(
                /* translators: 1: zoom mínimo, 2: zoom máximo */
                __( 'Nivel de zoom Leaflet (%1$d–%2$d).', 'sjb-wp-leaflet-map' ),
                self::ZOOM_MIN,
                self::ZOOM_MAX
            ),
        );
    }

    /**
     * Campo WPBakery: input type=number con min/max/step.
     *
     * @param array<string, mixed> $settings Ajustes del param.
     * @param string               $value    Valor actual.
     */
    public static function vc_sjb_number_form_field( $settings, $value ): string {
        $min  = isset( $settings['min'] ) ? (string) $settings['min'] : '';
        $max  = isset( $settings['max'] ) ? (string) $settings['max'] : '';
        $step = isset( $settings['step'] ) ? (string) $settings['step'] : '1';
        $val  = is_scalar( $value ) ? (string) $value : '';

        return sprintf(
            '<input name="%1$s" class="wpb_vc_param_value wpb-textinput %1$s %2$s" type="number" min="%3$s" max="%4$s" step="%5$s" value="%6$s" style="max-width:100px;" />',
            esc_attr( $settings['param_name'] ),
            esc_attr( $settings['type'] ),
            esc_attr( $min ),
            esc_attr( $max ),
            esc_attr( $step ),
            esc_attr( $val )
        );
    }

    /**
     * Acota el zoom al rango válido de Leaflet/OSM.
     */
    private static function normalize_zoom( string $zoom ): string {
        if ( ! is_numeric( $zoom ) ) {
            return '13';
        }

        $z = (int) $zoom;
        if ( $z < self::ZOOM_MIN ) {
            $z = self::ZOOM_MIN;
        } elseif ( $z > self::ZOOM_MAX ) {
            $z = self::ZOOM_MAX;
        }

        return (string) $z;
    }

    /**
     * Registra CSS/JS públicos (Leaflet + propio). No los encola aquí.
     */
    public static function register_public_scripts(): void {
        $leaflet = SJB_WP_LEAFLET_MAP::$path2assets . 'vendor/leaflet/';
        $slug    = SJB_WP_LEAFLET_MAP::$slug;
        $version = SJB_WP_LEAFLET_MAP::$version;

        wp_register_style(
            $slug . '-leaflet',
            $leaflet . 'leaflet.css',
            array(),
            '1.9.4'
        );

        wp_register_style(
            $slug . '-public',
            SJB_WP_LEAFLET_MAP::$path2assets . 'css/public.css',
            array( $slug . '-leaflet' ),
            $version
        );

        wp_register_script(
            $slug . '-leaflet',
            $leaflet . 'leaflet.js',
            array(),
            '1.9.4',
            true
        );

        wp_register_script(
            $slug . '-public',
            SJB_WP_LEAFLET_MAP::$path2assets . 'js/public.js',
            array( $slug . '-leaflet' ),
            $version,
            true
        );
    }

    /**
     * Imprime en el footer los assets públicos solo si el shortcode se usó.
     */
    public static function print_public_scripts(): void {
        if ( ! self::$add_script ) {
            return;
        }

        $slug = SJB_WP_LEAFLET_MAP::$slug;

        wp_print_styles(
            array(
                $slug . '-leaflet',
                $slug . '-public',
            )
        );
        wp_print_scripts(
            array(
                $slug . '-leaflet',
                $slug . '-public',
            )
        );
    }

    /**
     * Shortcode [sjb_leaflet_map]: contenedor del mapa.
     *
     * Atributos: lat, lng, zoom, width, height, id, marker_lat, marker_lng, marker_text.
     *
     * @param array<string, string>|string $atts Atributos del shortcode.
     */
    public static function shortcode_leaflet_map( $atts ): string {
        self::$add_script = 1;

        $atts = shortcode_atts(
            array(
                'lat'         => '42.4034506',
                'lng'         => '-8.8091448',
                'zoom'        => '13',
                'width'       => '100%',
                'height'      => '400px',
                'id'          => 'Leaflet Map',
                'marker_lat'  => '',
                'marker_lng'  => '',
                'marker_text' => '',
            ),
            $atts,
            'sjb_leaflet_map'
        );

        $map_id = sanitize_html_class( sanitize_title( $atts['id'] ) );
        if ( '' === $map_id ) {
            $map_id = 'leaflet-map';
        }

        $width  = self::normalize_css_size( $atts['width'], '100%' );
        $height = self::normalize_css_size( $atts['height'], '400px' );
        $zoom   = self::normalize_zoom( $atts['zoom'] );

        $style = sprintf(
            'width:%s;height:%s;',
            esc_attr( $width ),
            esc_attr( $height )
        );

        return sprintf(
            '<div id="%1$s" class="sjb-leaflet-map" style="%2$s" data-lat="%3$s" data-lng="%4$s" data-zoom="%5$s" data-marker-lat="%6$s" data-marker-lng="%7$s" data-marker-text="%8$s" data-marker-mode="both"></div>',
            esc_attr( $map_id ),
            $style,
            esc_attr( $atts['lat'] ),
            esc_attr( $atts['lng'] ),
            esc_attr( $zoom ),
            esc_attr( $atts['marker_lat'] ),
            esc_attr( $atts['marker_lng'] ),
            esc_attr( $atts['marker_text'] )
        );
    }

    /**
     * Shortcode [sjb_leaflet_collection]: mapa con marcadores de una colección.
     *
     * Atributos: collection (slug o ID), lat, lng, zoom, width, height, id.
     *
     * @param array<string, string>|string $atts Atributos del shortcode.
     */
    public static function shortcode_leaflet_collection( $atts ): string {
        self::$add_script = 1;

        $atts = shortcode_atts(
            array(
                'collection' => '',
                'lat'        => '42.4034506',
                'lng'        => '-8.8091448',
                'zoom'       => '13',
                'width'      => '100%',
                'height'     => '400px',
                'id'         => 'Leaflet Collection',
            ),
            $atts,
            'sjb_leaflet_collection'
        );

        $map_id = sanitize_html_class( sanitize_title( $atts['id'] ) );
        if ( '' === $map_id ) {
            $map_id = 'leaflet-collection';
        }

        $width  = self::normalize_css_size( $atts['width'], '100%' );
        $height = self::normalize_css_size( $atts['height'], '400px' );
        $zoom   = self::normalize_zoom( $atts['zoom'] );

        $style = sprintf(
            'width:%s;height:%s;',
            esc_attr( $width ),
            esc_attr( $height )
        );

        $markers = array();
        if ( '' !== trim( (string) $atts['collection'] ) && class_exists( 'SJB_WP_LEAFLET_MAP_Collections' ) ) {
            $markers = SJB_WP_LEAFLET_MAP_Collections::get_map_markers( $atts['collection'] );
        }

        $markers_json = wp_json_encode(
            $markers,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );
        if ( false === $markers_json ) {
            $markers_json = '[]';
        }

        return sprintf(
            '<div id="%1$s" class="sjb-leaflet-map" style="%2$s" data-lat="%3$s" data-lng="%4$s" data-zoom="%5$s" data-markers="%6$s"></div>',
            esc_attr( $map_id ),
            $style,
            esc_attr( $atts['lat'] ),
            esc_attr( $atts['lng'] ),
            esc_attr( $zoom ),
            esc_attr( $markers_json )
        );
    }

    /**
     * Normaliza width/height: si es solo número, añade "px".
     */
    private static function normalize_css_size( string $value, string $fallback ): string {
        $value = trim( $value );

        if ( '' === $value ) {
            return $fallback;
        }

        if ( is_numeric( $value ) ) {
            return $value . 'px';
        }

        return $value;
    }
}
