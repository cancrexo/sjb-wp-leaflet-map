<?php
/**
 * Plugin Name: SJB WP Leaflet Map
 * Plugin URI: https://www.sjbdixtal.es
 * Description: Mapas interactivos con Leaflet para WordPress.
 * Version: 1.0.0
 * Author: SJB Dixital
 * Author URI: https://www.sjbdixtal.es
 * Requires at least: 6.0
 * Requires PHP: 8.3
 * Text Domain: sjb-wp-leafleet-map
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package sjb-wp-leafleet-map
 */

defined( 'ABSPATH' ) || exit;

register_activation_hook( __FILE__, array( 'SJB_WP_LEAFLEET_MAP', 'on_activation' ) );
register_deactivation_hook( __FILE__, array( 'SJB_WP_LEAFLEET_MAP', 'on_deactivation' ) );

add_action( 'plugins_loaded', array( 'SJB_WP_LEAFLEET_MAP', 'init' ) );

/**
 * Plugin principal (singleton).
 */
class SJB_WP_LEAFLEET_MAP {

    /** @var string */
    public static $slug;
    /** @var string */
    public static $noslug;
    public static string $version = '1.0.0';
    public static string $title   = 'SJB WP Leaflet Map';
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
     * Constructor: traducciones, requisitos y hooks de administración.
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
                    esc_html_e( 'SJB WP Leaflet Map requiere PHP 8.3 o superior.', 'sjb-wp-leafleet-map' );
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
            'delete_onuninstall' => 0,
            'version'            => self::$version,
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
     * Enlace a ajustes en el listado de plugins.
     *
     * @param array<int, string> $links Enlaces actuales.
     * @return array<int, string>
     */
    public function plugin_settings_link( array $links ): array {
        $url = admin_url( 'options-general.php?page=' . self::$slug );

        array_unshift(
            $links,
            '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Ajustes', 'sjb-wp-leafleet-map' ) . '</a>'
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
    }

    /**
     * Pantalla de administración (pestañas) y guardado del switch.
     */
    public function render_admin_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $updated = false;

        if ( isset( $_POST['sjb_wp_leafleet_map_save'] ) ) {
            check_admin_referer( 'sjb_wp_leafleet_map_save_settings' );

            $options                         = self::get_options();
            $options['delete_onuninstall']   = isset( $_POST['delete_onuninstall'] ) ? 1 : 0;
            update_option( self::$noslug . '_options', $options );
            $updated = true;
        }

        $options = self::get_options();

        require self::$plugindir . 'templates/admin/settings.php';
    }

    /**
     * Activación: opciones por defecto si aún no existen.
     */
    public static function on_activation(): void {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        if ( version_compare( PHP_VERSION, '8.3', '<' ) ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die(
                esc_html__( 'SJB WP Leaflet Map requiere PHP 8.3 o superior.', 'sjb-wp-leafleet-map' ),
                esc_html__( 'Error de activación', 'sjb-wp-leafleet-map' ),
                array( 'back_link' => true )
            );
        }

        self::staticValues();

        if ( false === get_option( self::$noslug . '_options' ) ) {
            add_option( self::$noslug . '_options', self::default_options() );
        }
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
