<?php
/**
 * Plugin Name: SJB WP Leaflet Map
 * Plugin URI: https://www.sjbdixital.es
 * Description: Mapas interactivos con Leaflet para WordPress.
 * Version: 0.1.0
 * Author: SJB Dixital
 * Author URI: https://www.sjbdixital.es
 * Requires at least: 6.0
 * Requires PHP: 8.3
 * Text Domain: sjb-wp-leaflet-map
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package sjb-wp-leaflet-map
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/class-shortcodes.php';
require_once __DIR__ . '/includes/class-collections.php';
require_once __DIR__ . '/includes/class-exchange.php';
require_once __DIR__ . '/includes/class-ajax.php';

register_activation_hook( __FILE__, array( 'SJB_WP_LEAFLET_MAP', 'on_activation' ) );
register_deactivation_hook( __FILE__, array( 'SJB_WP_LEAFLET_MAP', 'on_deactivation' ) );

add_action( 'plugins_loaded', array( 'SJB_WP_LEAFLET_MAP', 'init' ) );

/**
 * Plugin principal (singleton): bootstrap, admin y opciones.
 */
class SJB_WP_LEAFLET_MAP {

    /** @var string */
    public static $slug;
    /** @var string */
    public static $noslug;
    public static string $version         = '0.1.0';
    public static string $leaflet_version = '1.9.4';
    public static string $title           = 'SJB WP Leaflet Map';
    /** Nombre del tamaño de imagen WP para el pin. */
    public const MARKER_ICON_IMAGE_SIZE = 'sjb-leaflet-marker';
    /** @var string */
    public static $plugindir;
    /** @var string */
    public static $pluginpath;
    /** @var string */
    public static $path2assets;

    /** @var self|null */
    protected static $instance = null;

    /**
     * Punto de entrada del singleton.
     */
    public static function init(): self {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Rutas y slugs. El slug sigue el nombre de carpeta (text domain).
     */
    public static function staticValues(): void {
        self::$pluginpath  = plugins_url( '', __FILE__ );
        self::$plugindir   = plugin_dir_path( __FILE__ );
        self::$slug        = dirname( plugin_basename( __FILE__ ) );
        self::$noslug      = str_replace( '-', '_', self::$slug );
        self::$path2assets = self::$pluginpath . '/assets/';
    }

    /**
     * Constructor: traducciones, requisitos, admin y shortcodes.
     */
    public function __construct() {
        self::staticValues();

        load_plugin_textdomain(
            self::$slug,
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );

        if ( ! self::check_requirements() ) {
            return;
        }

        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_settings_link' ) );

        SJB_WP_LEAFLET_MAP_Ajax::register();
        SJB_WP_LEAFLET_MAP_Shortcodes::register();

        add_action(
            'init',
            static function (): void {
                $size = absint( SJB_WP_LEAFLET_MAP::get_options()['marker_icon_size'] ?? 48 );
                if ( $size < 16 ) {
                    $size = 16;
                }
                if ( $size > 128 ) {
                    $size = 128;
                }
                add_image_size( SJB_WP_LEAFLET_MAP::MARKER_ICON_IMAGE_SIZE, $size, $size, false );
            }
        );

        // dbDelta en admin: añade columnas nuevas (p. ej. show_always).
        add_action( 'admin_init', array( 'SJB_WP_LEAFLET_MAP_Collections', 'create_tables' ) );
    }

    /**
     * Comprueba PHP 8.3 o superior.
     */
    private static function check_requirements(): bool {
        if ( version_compare( PHP_VERSION, '8.3', '<' ) ) {
            add_action(
                'admin_notices',
                static function (): void {
                    echo '<div class="notice notice-error"><p>';
                    esc_html_e( 'SJB WP Leaflet Map requiere PHP 8.3 o superior.', 'sjb-wp-leaflet-map' );
                    echo '</p></div>';
                }
            );

            return false;
        }

        return true;
    }

    /**
     * Valores por defecto de las opciones del plugin.
     *
     * @return array<string, mixed>
     */
    public static function default_options(): array {
        return array(
            'delete_onuninstall'     => 0,
            'version'                => self::$version,
            'marker_icon_source'     => 'leaflet',
            'marker_icon_attachment' => 0,
            'marker_icon_size'       => 48,
        );
    }

    /**
     * Datos de entorno para la pestaña Info (servidor, PHP, BD, versiones).
     *
     * @return array<int, array{title: string, rows: array<int, array{label: string, value: string, url?: string}>}>
     */
    public static function get_system_info(): array {
        global $wpdb;

        $na = '—';

        $yes = __( 'Sí', 'sjb-wp-leaflet-map' );
        $no  = __( 'No', 'sjb-wp-leaflet-map' );

        $ini = static function ( string $key ) use ( $na ): string {
            $raw = ini_get( $key );
            if ( false === $raw || '' === $raw ) {
                return $na;
            }

            return (string) $raw;
        };

        $bytes = static function ( string $raw ) use ( $na ): string {
            if ( '' === $raw || $na === $raw ) {
                return $na;
            }
            $pretty = size_format( wp_convert_hr_to_bytes( $raw ) );

            return $pretty ? $pretty . ' (' . $raw . ')' : $raw;
        };

        $wp_version = function_exists( 'wp_get_wp_version' )
            ? wp_get_wp_version()
            : get_bloginfo( 'version' );

        $woo = __( 'No activo', 'sjb-wp-leaflet-map' );
        if ( defined( 'WC_VERSION' ) ) {
            $woo = (string) WC_VERSION;
        } elseif ( class_exists( 'WooCommerce', false ) && function_exists( 'WC' ) ) {
            $woo = (string) WC()->version;
        }

        $theme        = wp_get_theme();
        $theme_label  = $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' );
        $parent_theme = $theme->parent();
        if ( $parent_theme ) {
            $theme_label .= ' (' . sprintf(
                /* translators: %s: nombre y versión del tema padre */
                __( 'hijo de %s', 'sjb-wp-leaflet-map' ),
                $parent_theme->get( 'Name' ) . ' ' . $parent_theme->get( 'Version' )
            ) . ')';
        }

        $server = isset( $_SERVER['SERVER_SOFTWARE'] )
            ? sanitize_text_field( wp_unslash( (string) $_SERVER['SERVER_SOFTWARE'] ) )
            : $na;

        $os = php_uname( 's' );
        $os_rel = php_uname( 'r' );
        if ( $os_rel ) {
            $os .= ' ' . $os_rel;
        }

        $db_version = (string) $wpdb->get_var( 'SELECT VERSION()' );
        if ( '' === $db_version && method_exists( $wpdb, 'db_server_info' ) ) {
            $db_version = (string) $wpdb->db_server_info();
        }
        $db_engine = ( false !== stripos( $db_version, 'mariadb' ) )
            ? 'MariaDB'
            : 'MySQL';

        $wanted_ext = array( 'curl', 'gd', 'imagick', 'intl', 'json', 'mbstring', 'mysqli', 'zip' );
        $ext_bits   = array();
        foreach ( $wanted_ext as $ext ) {
            if ( extension_loaded( $ext ) ) {
                $ext_bits[] = $ext;
            }
        }

        $mem_usage = size_format( (int) memory_get_usage( true ) );
        $mem_peak  = size_format( (int) memory_get_peak_usage( true ) );

        $wp_mem     = defined( 'WP_MEMORY_LIMIT' ) ? (string) WP_MEMORY_LIMIT : $na;
        $wp_mem_max = defined( 'WP_MAX_MEMORY_LIMIT' ) ? (string) WP_MAX_MEMORY_LIMIT : $na;

        $max_exec = $ini( 'max_execution_time' );
        if ( $na !== $max_exec ) {
            $max_exec .= ' s';
        }

        return array(
            array(
                'title' => __( 'Plugin', 'sjb-wp-leaflet-map' ),
                'rows'  => array(
                    array(
                        'label' => __( 'Nombre', 'sjb-wp-leaflet-map' ),
                        'value' => self::$title,
                    ),
                    array(
                        'label' => __( 'Versión del plugin', 'sjb-wp-leaflet-map' ),
                        'value' => self::$version,
                    ),
                    array(
                        'label' => __( 'Leaflet (vendor)', 'sjb-wp-leaflet-map' ),
                        'value' => self::$leaflet_version,
                    ),
                    array(
                        'label' => __( 'PHP requerido', 'sjb-wp-leaflet-map' ),
                        'value' => '8.3+',
                    ),
                    array(
                        'label' => __( 'WordPress requerido', 'sjb-wp-leaflet-map' ),
                        'value' => '6.0+',
                    ),
                ),
            ),
          
            array(
                'title' => __( 'WordPress y comercio', 'sjb-wp-leaflet-map' ),
                'rows'  => array(
                    array(
                        'label' => __( 'WordPress', 'sjb-wp-leaflet-map' ),
                        'value' => (string) $wp_version,
                    ),
                    array(
                        'label' => __( 'WooCommerce', 'sjb-wp-leaflet-map' ),
                        'value' => $woo,
                    ),
                    array(
                        'label' => __( 'Tema', 'sjb-wp-leaflet-map' ),
                        'value' => $theme_label,
                    ),
                    array(
                        'label' => __( 'URL del sitio', 'sjb-wp-leaflet-map' ),
                        'value' => home_url( '/' ),
                    ),
                    array(
                        'label' => __( 'Idioma', 'sjb-wp-leaflet-map' ),
                        'value' => get_locale(),
                    ),
                    array(
                        'label' => __( 'Multisitio', 'sjb-wp-leaflet-map' ),
                        'value' => is_multisite() ? $yes : $no,
                    ),
                    array(
                        'label' => __( 'WP_DEBUG', 'sjb-wp-leaflet-map' ),
                        'value' => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? $yes : $no,
                    ),
                    array(
                        'label' => __( 'Memoria WP', 'sjb-wp-leaflet-map' ),
                        'value' => $bytes( $wp_mem ),
                    ),
                    array(
                        'label' => __( 'Memoria WP (admin)', 'sjb-wp-leaflet-map' ),
                        'value' => $bytes( $wp_mem_max ),
                    ),
                ),
            ),
            array(
                'title' => __( 'Servidor', 'sjb-wp-leaflet-map' ),
                'rows'  => array(
                    array(
                        'label' => __( 'Software', 'sjb-wp-leaflet-map' ),
                        'value' => $server,
                    ),
                    array(
                        'label' => __( 'Sistema operativo', 'sjb-wp-leaflet-map' ),
                        'value' => ( '' !== $os ) ? $os : $na,
                    ),
                    array(
                        'label' => __( 'Arquitectura', 'sjb-wp-leaflet-map' ),
                        'value' => php_uname( 'm' ) ?: $na,
                    ),
                    array(
                        'label' => __( 'Hostname', 'sjb-wp-leaflet-map' ),
                        'value' => php_uname( 'n' ) ?: $na,
                    ),
                    array(
                        'label' => __( 'SAPI PHP', 'sjb-wp-leaflet-map' ),
                        'value' => PHP_SAPI,
                    ),
                ),
            ),
            array(
                'title' => __( 'PHP y memoria', 'sjb-wp-leaflet-map' ),
                'rows'  => array(
                    array(
                        'label' => __( 'Versión PHP', 'sjb-wp-leaflet-map' ),
                        'value' => PHP_VERSION,
                    ),
                    array(
                        'label' => __( 'memory_limit', 'sjb-wp-leaflet-map' ),
                        'value' => $bytes( $ini( 'memory_limit' ) ),
                    ),
                    array(
                        'label' => __( 'Uso actual', 'sjb-wp-leaflet-map' ),
                        'value' => $mem_usage ? $mem_usage : $na,
                    ),
                    array(
                        'label' => __( 'Pico de uso', 'sjb-wp-leaflet-map' ),
                        'value' => $mem_peak ? $mem_peak : $na,
                    ),
                    array(
                        'label' => __( 'max_execution_time', 'sjb-wp-leaflet-map' ),
                        'value' => $max_exec,
                    ),
                    array(
                        'label' => __( 'max_input_vars', 'sjb-wp-leaflet-map' ),
                        'value' => $ini( 'max_input_vars' ),
                    ),
                    array(
                        'label' => __( 'post_max_size', 'sjb-wp-leaflet-map' ),
                        'value' => $bytes( $ini( 'post_max_size' ) ),
                    ),
                    array(
                        'label' => __( 'upload_max_filesize', 'sjb-wp-leaflet-map' ),
                        'value' => $bytes( $ini( 'upload_max_filesize' ) ),
                    ),
                    array(
                        'label' => __( 'Extensiones', 'sjb-wp-leaflet-map' ),
                        'value' => $ext_bits ? implode( ', ', $ext_bits ) : $na,
                    ),
                ),
            ),
            array(
                'title' => __( 'Base de datos', 'sjb-wp-leaflet-map' ),
                'rows'  => array(
                    array(
                        'label' => __( 'Motor', 'sjb-wp-leaflet-map' ),
                        'value' => $db_engine,
                    ),
                    array(
                        'label' => __( 'Versión', 'sjb-wp-leaflet-map' ),
                        'value' => '' !== $db_version ? $db_version : $na,
                    ),
                    array(
                        'label' => __( 'Charset', 'sjb-wp-leaflet-map' ),
                        'value' => $wpdb->charset ? (string) $wpdb->charset : $na,
                    ),
                    array(
                        'label' => __( 'Collation', 'sjb-wp-leaflet-map' ),
                        'value' => $wpdb->collate ? (string) $wpdb->collate : $na,
                    ),
                    array(
                        'label' => __( 'Prefijo de tablas', 'sjb-wp-leaflet-map' ),
                        'value' => (string) $wpdb->prefix,
                    ),
                ),
            ),
            array(
                'title' => __( 'Autor', 'sjb-wp-leaflet-map' ),
                'rows'  => array(
                    array(
                        'label' => __( 'Nombre', 'sjb-wp-leaflet-map' ),
                        'value' => 'Daniel "Cancrexo" Prol',
                    ),
                    array(
                        'label' => __( 'Email', 'sjb-wp-leaflet-map' ),
                        'value' => 'cancrexo@gmail.com',
                        'url'   => 'mailto:cancrexo@gmail.com',
                    ),
                    array(
                        'label' => __( 'Empresa', 'sjb-wp-leaflet-map' ),
                        'value' => 'SJB Dixital',
                    ),
                    array(
                        'label' => __( 'Web', 'sjb-wp-leaflet-map' ),
                        'value' => 'https://www.sjbdixital.es',
                        'url'   => 'https://www.sjbdixital.es',
                    ),
                ),
            )
        );
    }

    /**
     * Opciones guardadas, fusionadas con los valores por defecto.
     *
     * @return array<string, mixed>
     */
    public static function get_options(): array {
        $saved = get_option( self::$noslug . '_options', array() );

        if ( ! is_array( $saved ) ) {
            $saved = array();
        }

        return array_merge( self::default_options(), $saved );
    }

    /**
     * Miniatura del pin: encaja en marker_icon_size sin recortar. La genera si falta.
     *
     * @return array{url: string, width: int, height: int}|null
     */
    public static function get_marker_icon_src( int $attachment_id ): ?array {
        if ( $attachment_id < 1 || ! wp_attachment_is_image( $attachment_id ) ) {
            return null;
        }

        $wanted = absint( self::get_options()['marker_icon_size'] ?? 48 );
        if ( $wanted < 16 ) {
            $wanted = 16;
        }
        if ( $wanted > 128 ) {
            $wanted = 128;
        }

        $file = get_attached_file( $attachment_id );
        if ( ! $file || ! is_readable( $file ) ) {
            return null;
        }

        $meta   = wp_get_attachment_metadata( $attachment_id );
        $orig_w = ( is_array( $meta ) && isset( $meta['width'] ) ) ? (int) $meta['width'] : 0;
        $orig_h = ( is_array( $meta ) && isset( $meta['height'] ) ) ? (int) $meta['height'] : 0;

        if ( $orig_w > 0 && $orig_h > 0 && $orig_w <= $wanted && $orig_h <= $wanted ) {
            $url = wp_get_attachment_url( $attachment_id );
            if ( ! $url ) {
                return null;
            }

            return array(
                'url'    => $url,
                'width'  => $orig_w,
                'height' => $orig_h,
            );
        }

        $size_name    = self::MARKER_ICON_IMAGE_SIZE;
        $needs_resize = true;
        $max_orig     = max( $orig_w, $orig_h );
        $expected_max = ( $max_orig > 0 ) ? min( $wanted, $max_orig ) : $wanted;

        if ( is_array( $meta ) && isset( $meta['sizes'][ $size_name ] ) && is_array( $meta['sizes'][ $size_name ] ) ) {
            $stored = $meta['sizes'][ $size_name ];
            $sw     = (int) ( $stored['width'] ?? 0 );
            $sh     = (int) ( $stored['height'] ?? 0 );
            $disk   = dirname( $file ) . '/' . ltrim( (string) ( $stored['file'] ?? '' ), '/' );
            if ( $sw > 0 && $sh > 0 && max( $sw, $sh ) === $expected_max && is_readable( $disk ) ) {
                $needs_resize = false;
            }
        }

        if ( $needs_resize ) {
            if ( ! function_exists( 'image_make_intermediate_size' ) ) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
            }
            $resized = image_make_intermediate_size( $file, $wanted, $wanted, false );
            if ( is_array( $resized ) && ! empty( $resized['file'] ) ) {
                if ( ! is_array( $meta ) ) {
                    $meta = array();
                }
                if ( ! isset( $meta['sizes'] ) || ! is_array( $meta['sizes'] ) ) {
                    $meta['sizes'] = array();
                }
                $meta['sizes'][ $size_name ] = $resized;
                wp_update_attachment_metadata( $attachment_id, $meta );
            }
        }

        $src = wp_get_attachment_image_src( $attachment_id, $size_name );
        if ( is_array( $src ) && ! empty( $src[0] ) ) {
            return array(
                'url'    => (string) $src[0],
                'width'  => isset( $src[1] ) ? (int) $src[1] : $wanted,
                'height' => isset( $src[2] ) ? (int) $src[2] : $wanted,
            );
        }

        $full = wp_get_attachment_image_src( $attachment_id, 'full' );
        if ( ! is_array( $full ) || empty( $full[0] ) ) {
            return null;
        }

        return array(
            'url'    => (string) $full[0],
            'width'  => isset( $full[1] ) ? (int) $full[1] : 0,
            'height' => isset( $full[2] ) ? (int) $full[2] : 0,
        );
    }

    /**
     * Enlace a ajustes en el listado de plugins.
     *
     * @param array<int, string> $links Enlaces actuales.
     * @return array<int, string>
     */
    public function plugin_settings_link( array $links ): array {
        $url = admin_url( 'options-general.php?page=' . self::$slug );

        array_unshift(
            $links,
            '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Ajustes', 'sjb-wp-leaflet-map' ) . '</a>'
        );

        return $links;
    }

    /**
     * Entrada de menú en Ajustes.
     */
    public function add_admin_menu(): void {
        add_options_page(
            self::$title,
            self::$title,
            'manage_options',
            self::$slug,
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Bootstrap 5.3 y CSS propio, solo en la página de este plugin.
     *
     * @param string $hook Hook de la pantalla actual.
     */
    public function enqueue_admin_assets( string $hook ): void {
        if ( 'settings_page_' . self::$slug !== $hook ) {
            return;
        }

        wp_enqueue_media();

        $bootstrap = self::$path2assets . 'vendor/bootstrap/';

        wp_enqueue_style(
            self::$slug . '-bootstrap',
            $bootstrap . 'bootstrap.min.css',
            array(),
            '5.3.3'
        );

        wp_enqueue_style(
            self::$slug . '-admin',
            self::$path2assets . 'css/admin.css',
            array( self::$slug . '-bootstrap' ),
            self::$version
        );

        wp_enqueue_script(
            self::$slug . '-bootstrap',
            $bootstrap . 'bootstrap.bundle.min.js',
            array(),
            '5.3.3',
            true
        );

        wp_enqueue_script(
            self::$slug . '-admin',
            self::$path2assets . 'js/admin.js',
            array( self::$slug . '-bootstrap' ),
            self::$version,
            true
        );

        wp_localize_script(
            self::$slug . '-admin',
            'sjbWpLeafletMapAdmin',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( SJB_WP_LEAFLET_MAP_Ajax::NONCE_ACTION ),
                'i18n'    => array(
                    'okGeneric'         => __( 'Operación correcta.', 'sjb-wp-leaflet-map' ),
                    'errorGeneric'      => __( 'Ha ocurrido un error.', 'sjb-wp-leaflet-map' ),
                    'networkError'      => __( 'Error de red. Inténtalo de nuevo.', 'sjb-wp-leaflet-map' ),
                    'close'             => __( 'Cerrar', 'sjb-wp-leaflet-map' ),
                    'collectionNew'     => __( 'Nueva colección', 'sjb-wp-leaflet-map' ),
                    'collectionEdit'    => __( 'Editar colección', 'sjb-wp-leaflet-map' ),
                    'collectionCreate'  => __( 'Crear colección', 'sjb-wp-leaflet-map' ),
                    'collectionSave'    => __( 'Guardar cambios', 'sjb-wp-leaflet-map' ),
                    'markerSaved'       => __( 'Marcador guardado.', 'sjb-wp-leaflet-map' ),
                    'markerDeleted'     => __( 'Marcador eliminado.', 'sjb-wp-leaflet-map' ),
                    'markerInvalid'     => __( 'Latitud y longitud deben ser números válidos.', 'sjb-wp-leaflet-map' ),
                    'markerConfirmDel'  => __( '¿Eliminar este marcador?', 'sjb-wp-leaflet-map' ),
                    'exportTodo'        => __( 'Exportar: pendiente de implementar.', 'sjb-wp-leaflet-map' ),
                    'exportTitle'       => __( 'Exportar colección', 'sjb-wp-leaflet-map' ),
                    'exportError'       => __( 'No se pudo exportar la colección.', 'sjb-wp-leaflet-map' ),
                    'exportKmzMissing'  => __( 'KMZ requiere la extensión ZIP de PHP.', 'sjb-wp-leaflet-map' ),
                    'importValidated'   => __( 'Archivo válido', 'sjb-wp-leaflet-map' ),
                    'importNeedFile'    => __( 'Selecciona un archivo primero.', 'sjb-wp-leaflet-map' ),
                    'importNeedName'    => __( 'Indica un nombre para la colección.', 'sjb-wp-leaflet-map' ),
                    'importNoKmz'       => __( 'El KMZ es un ZIP binario. Descomprímelo e importa el archivo KML.', 'sjb-wp-leaflet-map' ),
                    'duplicateTodo'     => __( 'Duplicar: pendiente de implementar.', 'sjb-wp-leaflet-map' ),
                    'markerActive'      => __( 'Activo (clic para desactivar)', 'sjb-wp-leaflet-map' ),
                    'markerInactive'    => __( 'Inactivo (clic para activar)', 'sjb-wp-leaflet-map' ),
                    'iconSelect'        => __( 'Seleccionar imagen', 'sjb-wp-leaflet-map' ),
                    'iconChange'        => __( 'Cambiar imagen', 'sjb-wp-leaflet-map' ),
                    'iconTitle'         => __( 'Icono del marcador', 'sjb-wp-leaflet-map' ),
                    'iconCollection'    => __( 'Icono de la colección (clic para cambiar)', 'sjb-wp-leaflet-map' ),
                    'iconOwn'           => __( 'Icono propio (clic para cambiar)', 'sjb-wp-leaflet-map' ),
                ),
                'leafletIconUrl' => self::$path2assets . 'vendor/leaflet/images/marker-icon.png',
            )
        );
    }

    /**
     * Pantalla de administración (pestañas). La escritura va por AJAX.
     */
    public function render_admin_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( (string) wp_unslash( $_GET['tab'] ) ) : 'configuracion';
        if ( ! in_array( $active_tab, array( 'configuracion', 'marcadores', 'info' ), true ) ) {
            $active_tab = 'configuracion';
        }

        $options = self::get_options();

        require self::$plugindir . 'templates/admin/settings.php';
    }

    /**
     * Activación: opciones por defecto y tablas de colecciones/marcadores.
     */
    public static function on_activation(): void {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        if ( version_compare( PHP_VERSION, '8.3', '<' ) ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die(
                esc_html__( 'SJB WP Leaflet Map requiere PHP 8.3 o superior.', 'sjb-wp-leaflet-map' ),
                esc_html__( 'Error de activación', 'sjb-wp-leaflet-map' ),
                array( 'back_link' => true )
            );
        }

        self::staticValues();

        $option_name     = self::$noslug . '_options';
        $legacy_option   = 'sjb_wp_leafleet_map_options'; // Typo previo (3 e).
        $existing_option = get_option( $option_name, false );

        // Migrar option del slug mal escrito si aún existe en BD.
        if ( false === $existing_option ) {
            $legacy = get_option( $legacy_option, false );
            if ( false !== $legacy && is_array( $legacy ) ) {
                add_option( $option_name, $legacy );
                delete_option( $legacy_option );
            } else {
                add_option( $option_name, self::default_options() );
            }
        }

        SJB_WP_LEAFLET_MAP_Collections::create_tables();
    }

    /**
     * Desactivación: no borra datos.
     */
    public static function on_deactivation(): void {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
    }
}
