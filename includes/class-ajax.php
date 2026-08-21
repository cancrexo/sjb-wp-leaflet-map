<?php
/**
 * Handlers AJAX del admin (escritura en BD).
 *
 * @package sjb-wp-leaflet-map
 * @author  Daniel "Cancrexo" Prol
 * @email   cancrexo@gmail.com
 */

defined( 'ABSPATH' ) || exit;

/**
 * Acciones wp_ajax_* centralizadas para ajustes, colecciones y marcadores.
 */
class SJB_WP_LEAFLET_MAP_Ajax {

    /** Prefijo de actions: sjb_wp_leaflet_map_* */
    public const ACTION_PREFIX = 'sjb_wp_leaflet_map_';

    /** Nonce action compartido con admin.js */
    public const NONCE_ACTION = 'sjb_wp_leaflet_map_ajax';

    /**
     * Registra los hooks AJAX (solo usuarios autenticados).
     */
    public static function register(): void {
        $actions = array(
            'save_settings',
            'save_collection',
            'delete_collection',
            'save_marker',
            'delete_marker',
            'toggle_marker_active',
        );

        foreach ( $actions as $action ) {
            add_action(
                'wp_ajax_' . self::ACTION_PREFIX . $action,
                array( self::class, 'handle_' . $action )
            );
        }
    }

    /**
     * Comprueba capacidad y nonce. Aborta con JSON de error si falla.
     */
    private static function guard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                array( 'message' => __( 'No tienes permisos para esta acción.', 'sjb-wp-leaflet-map' ) ),
                403
            );
        }

        $nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Sesión no válida. Recarga la página e inténtalo de nuevo.', 'sjb-wp-leaflet-map' ) ),
                403
            );
        }
    }

    /**
     * URL de la pantalla admin con query args.
     *
     * @param array<string, scalar> $args Argumentos extra.
     */
    private static function admin_url( array $args = array() ): string {
        return add_query_arg(
            array_merge(
                array( 'page' => SJB_WP_LEAFLET_MAP::$slug ),
                $args
            ),
            admin_url( 'options-general.php' )
        );
    }

    /**
     * Guarda el switch «borrar datos al desinstalar».
     */
    public static function handle_save_settings(): void {
        self::guard();

        $options                       = SJB_WP_LEAFLET_MAP::get_options();
        $options['delete_onuninstall'] = isset( $_POST['delete_onuninstall'] ) ? 1 : 0;

        $icon_source = isset( $_POST['marker_icon_source'] ) ? sanitize_key( (string) wp_unslash( $_POST['marker_icon_source'] ) ) : 'leaflet';
        if ( ! in_array( $icon_source, array( 'leaflet', 'media' ), true ) ) {
            $icon_source = 'leaflet';
        }
        $icon_attachment = absint( $_POST['marker_icon_attachment'] ?? 0 );
        if ( 'media' !== $icon_source ) {
            $icon_attachment = 0;
        }

        $options['marker_icon_source']     = $icon_source;
        $options['marker_icon_attachment'] = $icon_attachment;
        update_option( SJB_WP_LEAFLET_MAP::$noslug . '_options', $options );

        wp_send_json_success(
            array(
                'message' => __( 'Ajustes guardados.', 'sjb-wp-leaflet-map' ),
            )
        );
    }

    /**
     * Crea o actualiza una colección.
     */
    public static function handle_save_collection(): void {
        self::guard();

        $id = SJB_WP_LEAFLET_MAP_Collections::save_collection(
            array(
                'id'                 => absint( $_POST['collection_id'] ?? 0 ),
                'name'               => wp_unslash( (string) ( $_POST['collection_name'] ?? '' ) ),
                'slug'               => wp_unslash( (string) ( $_POST['collection_slug'] ?? '' ) ),
                'description'        => wp_unslash( (string) ( $_POST['collection_description'] ?? '' ) ),
                'icon_source'        => wp_unslash( (string) ( $_POST['collection_icon_source'] ?? 'inherit' ) ),
                'icon_attachment_id' => absint( $_POST['collection_icon_attachment'] ?? 0 ),
            )
        );

        if ( $id < 1 ) {
            wp_send_json_error(
                array(
                    'message' => __( 'No se pudo guardar la colección. El nombre es obligatorio.', 'sjb-wp-leaflet-map' ),
                )
            );
        }

        $was_new = absint( $_POST['collection_id'] ?? 0 ) < 1;

        // Tras crear: ir a marcadores. Tras editar (modal): volver al listado.
        $redirect = $was_new
            ? self::admin_url(
                array(
                    'tab'           => 'marcadores',
                    'collection_id' => $id,
                )
            )
            : self::admin_url( array( 'tab' => 'marcadores' ) );

        wp_send_json_success(
            array(
                'message'  => __( 'Colección guardada.', 'sjb-wp-leaflet-map' ),
                'id'       => $id,
                'redirect' => $redirect,
            )
        );
    }

    /**
     * Elimina una colección y sus marcadores.
     */
    public static function handle_delete_collection(): void {
        self::guard();

        $id = absint( $_POST['collection_id'] ?? 0 );
        if ( ! SJB_WP_LEAFLET_MAP_Collections::delete_collection( $id ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'No se pudo eliminar la colección.', 'sjb-wp-leaflet-map' ),
                )
            );
        }

        wp_send_json_success(
            array(
                'message'  => __( 'Colección eliminada.', 'sjb-wp-leaflet-map' ),
                'redirect' => self::admin_url( array( 'tab' => 'marcadores' ) ),
            )
        );
    }

    /**
     * Crea o actualiza un marcador.
     */
    public static function handle_save_marker(): void {
        self::guard();

        $collection_id = absint( $_POST['collection_id'] ?? 0 );
        $id            = SJB_WP_LEAFLET_MAP_Collections::save_marker(
            array(
                'id'            => absint( $_POST['marker_id'] ?? 0 ),
                'collection_id' => $collection_id,
                'lat'           => wp_unslash( (string) ( $_POST['marker_lat'] ?? '' ) ),
                'lng'           => wp_unslash( (string) ( $_POST['marker_lng'] ?? '' ) ),
                'text'          => wp_unslash( (string) ( $_POST['marker_text'] ?? '' ) ),
                'display_mode'  => sanitize_key( (string) ( $_POST['marker_display_mode'] ?? 'both' ) ),
                'sort_order'    => absint( $_POST['marker_sort_order'] ?? 0 ),
                'is_active'     => isset( $_POST['marker_is_active'] ) ? absint( $_POST['marker_is_active'] ) : 1,
            )
        );

        if ( $id < 1 ) {
            wp_send_json_error(
                array(
                    'message' => __( 'No se pudo guardar el marcador. Revisa colección y coordenadas.', 'sjb-wp-leaflet-map' ),
                )
            );
        }

        wp_send_json_success(
            array(
                'message' => __( 'Marcador guardado.', 'sjb-wp-leaflet-map' ),
                'id'      => $id,
            )
        );
    }

    /**
     * Elimina un marcador.
     */
    public static function handle_delete_marker(): void {
        self::guard();

        $marker_id = absint( $_POST['marker_id'] ?? 0 );

        if ( ! SJB_WP_LEAFLET_MAP_Collections::delete_marker( $marker_id ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'No se pudo eliminar el marcador.', 'sjb-wp-leaflet-map' ),
                )
            );
        }

        wp_send_json_success(
            array(
                'message' => __( 'Marcador eliminado.', 'sjb-wp-leaflet-map' ),
            )
        );
    }

    /**
     * Alterna el estado activo/inactivo de un marcador.
     */
    public static function handle_toggle_marker_active(): void {
        self::guard();

        $marker_id = absint( $_POST['marker_id'] ?? 0 );
        $marker    = SJB_WP_LEAFLET_MAP_Collections::get_marker( $marker_id );

        if ( ! $marker ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Marcador no encontrado.', 'sjb-wp-leaflet-map' ),
                )
            );
        }

        // Sin columna aún o null → se considera activo.
        $currently_active = ! isset( $marker->is_active ) || (int) $marker->is_active === 1;
        $new_active       = ! $currently_active;

        if ( ! SJB_WP_LEAFLET_MAP_Collections::set_marker_active( $marker_id, $new_active ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'No se pudo cambiar el estado del marcador.', 'sjb-wp-leaflet-map' ),
                )
            );
        }

        wp_send_json_success(
            array(
                'message'   => $new_active
                    ? __( 'Marcador activado.', 'sjb-wp-leaflet-map' )
                    : __( 'Marcador desactivado.', 'sjb-wp-leaflet-map' ),
                'is_active' => $new_active ? 1 : 0,
            )
        );
    }
}
