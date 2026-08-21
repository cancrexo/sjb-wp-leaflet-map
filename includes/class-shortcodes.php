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

        $category = __( 'SJB Shortcodes', 'sjb-wp-leaflet-map' );

        $shortcodes = array(
            array(
                'base'        => 'sjb_leaflet_map',
                'name'        => __( 'SJB Leaflet Map', 'sjb-wp-leaflet-map' ),
                'description' => __( 'Mapa interactivo Leaflet (OpenStreetMap).', 'sjb-wp-leaflet-map' ),
                'params'      => array(
                    array(
                        'type'        => 'textfield',
                        'heading'     => __( 'Latitud', 'sjb-wp-leaflet-map' ),
                        'param_name'  => 'lat',
                        'value'       => '40.4168',
                        'admin_label' => true,
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => __( 'Longitud', 'sjb-wp-leaflet-map' ),
                        'param_name'  => 'lng',
                        'value'       => '-3.7038',
                        'admin_label' => true,
                    ),
                    array(
                        'type'       => 'textfield',
                        'heading'    => __( 'Zoom', 'sjb-wp-leaflet-map' ),
                        'param_name' => 'zoom',
                        'value'      => '13',
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => __( 'Ancho', 'sjb-wp-leaflet-map' ),
                        'param_name'  => 'width',
                        'value'       => '100%',
                        'description' => __( 'Ej. 100% o 600 (px).', 'sjb-wp-leaflet-map' ),
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => __( 'Alto', 'sjb-wp-leaflet-map' ),
                        'param_name'  => 'height',
                        'value'       => '400px',
                        'description' => __( 'Ej. 400px o 400.', 'sjb-wp-leaflet-map' ),
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => __( 'ID del contenedor', 'sjb-wp-leaflet-map' ),
                        'param_name'  => 'id',
                        'value'       => 'Leaflet Map',
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
     * Atributos: lat, lng, zoom, width, height, id.
     *
     * @param array<string, string>|string $atts Atributos del shortcode.
     */
    public static function shortcode_leaflet_map( $atts ): string {
        self::$add_script = 1;

        $atts = shortcode_atts(
            array(
                'lat'    => '40.4168',
                'lng'    => '-3.7038',
                'zoom'   => '13',
                'width'  => '100%',
                'height' => '400px',
                'id'     => 'Leaflet Map',
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

        $style = sprintf(
            'width:%s;height:%s;',
            esc_attr( $width ),
            esc_attr( $height )
        );

        return sprintf(
            '<div id="%1$s" class="sjb-leaflet-map" style="%2$s" data-lat="%3$s" data-lng="%4$s" data-zoom="%5$s"></div>',
            esc_attr( $map_id ),
            $style,
            esc_attr( $atts['lat'] ),
            esc_attr( $atts['lng'] ),
            esc_attr( $atts['zoom'] )
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
