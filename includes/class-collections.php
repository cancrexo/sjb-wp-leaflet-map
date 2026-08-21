<?php
/**
 * Colecciones y marcadores: esquema BD y CRUD.
 *
 * @package sjb-wp-leaflet-map
 * @author  Daniel "Cancrexo" Prol
 * @email   cancrexo@gmail.com
 */

defined( 'ABSPATH' ) || exit;

/**
 * Persistencia de colecciones y marcadores (dos tablas).
 */
class SJB_WP_LEAFLET_MAP_Collections {

    /**
     * Nombre de la tabla de colecciones (con prefijo WP).
     */
    public static function table_collections(): string {
        global $wpdb;

        return $wpdb->prefix . 'sjb_wp_leaflet_map_collections';
    }

    /**
     * Nombre de la tabla de marcadores (con prefijo WP).
     */
    public static function table_markers(): string {
        global $wpdb;

        return $wpdb->prefix . 'sjb_wp_leaflet_map_markers';
    }

    /**
     * Crea las tablas de colecciones y marcadores (activación).
     */
    public static function create_tables(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset   = $wpdb->get_charset_collate();
        $t_coll    = self::table_collections();
        $t_markers = self::table_markers();

        $sql_collections = "CREATE TABLE {$t_coll} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL DEFAULT '',
            slug varchar(191) NOT NULL DEFAULT '',
            description text NULL,
            icon_source varchar(20) NOT NULL DEFAULT 'inherit',
            icon_attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) {$charset};";

        $sql_markers = "CREATE TABLE {$t_markers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            collection_id bigint(20) unsigned NOT NULL DEFAULT 0,
            lat decimal(10,7) NOT NULL DEFAULT 0,
            lng decimal(10,7) NOT NULL DEFAULT 0,
            text text NULL,
            show_on_hover tinyint(1) NOT NULL DEFAULT 1,
            show_on_click tinyint(1) NOT NULL DEFAULT 1,
            show_always tinyint(1) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            icon_source varchar(20) NOT NULL DEFAULT 'inherit',
            icon_attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY collection_id (collection_id)
        ) {$charset};";

        dbDelta( $sql_collections );
        dbDelta( $sql_markers );
    }

    /**
     * Elimina las dos tablas (desinstalación).
     */
    public static function drop_tables(): void {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- nombres de tabla fijos del plugin.
        $wpdb->query( 'DROP TABLE IF EXISTS `' . self::table_markers() . '`' );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( 'DROP TABLE IF EXISTS `' . self::table_collections() . '`' );
    }

    /**
     * Lista blanca wp_kses para el texto del marcador.
     *
     * @return array<string, array<string, bool>>
     */
    public static function allowed_html(): array {
        return array(
            'strong' => array(),
            'b'      => array(),
            'u'      => array(),
            'br'     => array(),
            'a'      => array(
                'href'   => true,
                'title'  => true,
                'target' => true,
                'rel'    => true,
            ),
        );
    }

    /**
     * Sanitiza el HTML del texto del marcador.
     */
    public static function sanitize_marker_text( string $text ): string {
        return wp_kses( $text, self::allowed_html() );
    }

    /**
     * Todas las colecciones (más recientes primero).
     *
     * @return list<object>
     */
    public static function get_collections(): array {
        global $wpdb;

        $table = self::table_collections();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY name ASC" );

        return is_array( $rows ) ? $rows : array();
    }

    /**
     * Una colección por ID.
     */
    public static function get_collection( int $id ): ?object {
        global $wpdb;

        if ( $id < 1 ) {
            return null;
        }

        $table = self::table_collections();
        $row   = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        return $row ?: null;
    }

    /**
     * Inserta o actualiza una colección. Devuelve el ID o 0 si falla.
     *
     * @param array{id?:int,name:string,slug?:string,description?:string,icon_source?:string,icon_attachment_id?:int} $data Datos.
     */
    public static function save_collection( array $data ): int {
        global $wpdb;

        $id          = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
        $name        = sanitize_text_field( $data['name'] ?? '' );
        $slug_input  = isset( $data['slug'] ) ? sanitize_title( (string) $data['slug'] ) : '';
        $description = sanitize_textarea_field( $data['description'] ?? '' );
        $icon_source = sanitize_key( (string) ( $data['icon_source'] ?? 'inherit' ) );
        if ( ! in_array( $icon_source, array( 'inherit', 'leaflet', 'media' ), true ) ) {
            $icon_source = 'inherit';
        }
        $icon_attachment_id = absint( $data['icon_attachment_id'] ?? 0 );
        if ( 'media' !== $icon_source ) {
            $icon_attachment_id = 0;
        }
        $now = current_time( 'mysql' );

        if ( '' === $name ) {
            return 0;
        }

        $slug = '' !== $slug_input ? $slug_input : sanitize_title( $name );
        if ( '' === $slug ) {
            $slug = 'coleccion';
        }
        $slug = self::unique_collection_slug( $slug, $id );

        $table = self::table_collections();

        $row = array(
            'name'               => $name,
            'slug'               => $slug,
            'description'        => $description,
            'icon_source'        => $icon_source,
            'icon_attachment_id' => $icon_attachment_id,
            'updated_at'         => $now,
        );

        if ( $id > 0 && self::get_collection( $id ) ) {
            $wpdb->update(
                $table,
                $row,
                array( 'id' => $id ),
                array( '%s', '%s', '%s', '%s', '%d', '%s' ),
                array( '%d' )
            );

            return $id;
        }

        $row['created_at'] = $now;
        $wpdb->insert(
            $table,
            $row,
            array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * Borra colección y sus marcadores.
     */
    public static function delete_collection( int $id ): bool {
        global $wpdb;

        if ( $id < 1 ) {
            return false;
        }

        $t_markers = self::table_markers();
        $t_coll    = self::table_collections();

        $wpdb->delete( $t_markers, array( 'collection_id' => $id ), array( '%d' ) );
        $deleted = $wpdb->delete( $t_coll, array( 'id' => $id ), array( '%d' ) );

        return false !== $deleted;
    }

    /**
     * Marcadores de una colección (por sort_order, luego id).
     *
     * @return list<object>
     */
    public static function get_markers( int $collection_id ): array {
        global $wpdb;

        if ( $collection_id < 1 ) {
            return array();
        }

        $table = self::table_markers();
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE collection_id = %d ORDER BY sort_order ASC, id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $collection_id
            )
        );

        return is_array( $rows ) ? $rows : array();
    }

    /**
     * Colección por slug.
     */
    public static function get_collection_by_slug( string $slug ): ?object {
        global $wpdb;

        $slug = sanitize_title( $slug );
        if ( '' === $slug ) {
            return null;
        }

        $table = self::table_collections();
        $row   = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s LIMIT 1", $slug ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        return $row ?: null;
    }

    /**
     * Modo de visualización de un marcador (hover|click|both|always).
     */
    public static function resolve_marker_display_mode( object $m ): string {
        if ( ! empty( $m->show_always ) ) {
            return 'always';
        }

        $hover = (int) $m->show_on_hover;
        $click = (int) $m->show_on_click;

        if ( $hover && $click ) {
            return 'both';
        }
        if ( $hover ) {
            return 'hover';
        }
        if ( $click ) {
            return 'click';
        }

        return 'both';
    }

    /**
     * Marcadores activos de una colección para el mapa (JSON frontend).
     *
     * @param int|string $id_or_slug ID numérico o slug.
     * @return list<array{lat:float,lng:float,text:string,mode:string,icon_url?:string,icon_width?:int,icon_height?:int}>
     */
    public static function get_map_markers( $id_or_slug ): array {
        $collection = null;

        if ( is_numeric( $id_or_slug ) && (int) $id_or_slug > 0 ) {
            $collection = self::get_collection( (int) $id_or_slug );
        } else {
            $collection = self::get_collection_by_slug( (string) $id_or_slug );
        }

        if ( ! $collection ) {
            return array();
        }

        $out = array();
        foreach ( self::get_markers( (int) $collection->id ) as $m ) {
            if ( isset( $m->is_active ) && (int) $m->is_active === 0 ) {
                continue;
            }

            $item = array(
                'lat'  => (float) $m->lat,
                'lng'  => (float) $m->lng,
                'text' => (string) $m->text,
                'mode' => self::resolve_marker_display_mode( $m ),
            );

            $marker_source = isset( $m->icon_source ) ? sanitize_key( (string) $m->icon_source ) : 'inherit';
            if ( 'media' === $marker_source ) {
                $resolved = self::resolve_map_icon( $collection, $m );
                if ( 'media' === $resolved['source'] && '' !== $resolved['url'] ) {
                    $item['icon_url']    = $resolved['url'];
                    $item['icon_width']  = $resolved['width'];
                    $item['icon_height'] = $resolved['height'];
                }
            }

            $out[] = $item;
        }

        return $out;
    }

    /**
     * Resuelve el icono del mapa: marcador (si hay) → colección → ajustes globales.
     *
     * @param object|null $collection Colección o null (mapa simple / heredar).
     * @param object|null $marker     Marcador o null (usar colección / global).
     * @return array{source:string,url:string,width:int,height:int}
     */
    public static function resolve_map_icon( ?object $collection = null, ?object $marker = null ): array {
        $marker_source = ( $marker && isset( $marker->icon_source ) )
            ? sanitize_key( (string) $marker->icon_source )
            : 'inherit';

        if ( 'media' === $marker_source ) {
            $marker_att = isset( $marker->icon_attachment_id ) ? absint( $marker->icon_attachment_id ) : 0;
            if ( $marker_att > 0 ) {
                $img = wp_get_attachment_image_src( $marker_att, 'full' );
                if ( is_array( $img ) && ! empty( $img[0] ) ) {
                    return array(
                        'source' => 'media',
                        'url'    => (string) $img[0],
                        'width'  => isset( $img[1] ) ? (int) $img[1] : 0,
                        'height' => isset( $img[2] ) ? (int) $img[2] : 0,
                    );
                }
            }
        }

        $source = 'leaflet';
        $att_id = 0;

        $coll_source = ( $collection && isset( $collection->icon_source ) )
            ? sanitize_key( (string) $collection->icon_source )
            : 'inherit';

        if ( 'leaflet' === $coll_source ) {
            $source = 'leaflet';
        } elseif ( 'media' === $coll_source ) {
            $source = 'media';
            $att_id = isset( $collection->icon_attachment_id ) ? absint( $collection->icon_attachment_id ) : 0;
        } else {
            $options = SJB_WP_LEAFLET_MAP::get_options();
            $source  = isset( $options['marker_icon_source'] ) ? sanitize_key( (string) $options['marker_icon_source'] ) : 'leaflet';
            if ( ! in_array( $source, array( 'leaflet', 'media' ), true ) ) {
                $source = 'leaflet';
            }
            $att_id = isset( $options['marker_icon_attachment'] ) ? absint( $options['marker_icon_attachment'] ) : 0;
        }

        if ( 'media' === $source && $att_id > 0 ) {
            $img = wp_get_attachment_image_src( $att_id, 'full' );
            if ( is_array( $img ) && ! empty( $img[0] ) ) {
                return array(
                    'source' => 'media',
                    'url'    => (string) $img[0],
                    'width'  => isset( $img[1] ) ? (int) $img[1] : 0,
                    'height' => isset( $img[2] ) ? (int) $img[2] : 0,
                );
            }
        }

        return array(
            'source' => 'leaflet',
            'url'    => '',
            'width'  => 0,
            'height' => 0,
        );
    }

    /**
     * Colección por ID o slug.
     *
     * @param int|string $id_or_slug ID o slug.
     */
    public static function get_collection_by_ref( $id_or_slug ): ?object {
        if ( is_numeric( $id_or_slug ) && (int) $id_or_slug > 0 ) {
            return self::get_collection( (int) $id_or_slug );
        }

        return self::get_collection_by_slug( (string) $id_or_slug );
    }

    /**
     * Un marcador por ID.
     */
    public static function get_marker( int $id ): ?object {
        global $wpdb;

        if ( $id < 1 ) {
            return null;
        }

        $table = self::table_markers();
        $row   = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        return $row ?: null;
    }

    /**
     * Inserta o actualiza un marcador. Devuelve el ID o 0 si falla.
     *
     * @param array<string, mixed> $data Datos del marcador.
     */
    public static function save_marker( array $data ): int {
        global $wpdb;

        $id            = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
        $collection_id = isset( $data['collection_id'] ) ? absint( $data['collection_id'] ) : 0;
        $lat_raw       = trim( (string) ( $data['lat'] ?? '' ) );
        $lng_raw       = trim( (string) ( $data['lng'] ?? '' ) );
        $text          = self::sanitize_marker_text( (string) ( $data['text'] ?? '' ) );
        $sort_order    = isset( $data['sort_order'] ) ? (int) $data['sort_order'] : 0;
        $now           = current_time( 'mysql' );

        $mode = isset( $data['display_mode'] ) ? (string) $data['display_mode'] : 'both';
        if ( ! in_array( $mode, array( 'hover', 'click', 'both', 'always' ), true ) ) {
            $mode = 'both';
        }

        $show_always   = ( 'always' === $mode ) ? 1 : 0;
        $show_on_hover = ( 'hover' === $mode || 'both' === $mode ) ? 1 : 0;
        $show_on_click = ( 'click' === $mode || 'both' === $mode ) ? 1 : 0;

        if ( $collection_id < 1 || ! self::get_collection( $collection_id ) ) {
            return 0;
        }

        $lat_raw = str_replace( ',', '.', $lat_raw );
        $lng_raw = str_replace( ',', '.', $lng_raw );

        if ( ! is_numeric( $lat_raw ) || ! is_numeric( $lng_raw ) ) {
            return 0;
        }

        $lat = (float) $lat_raw;
        $lng = (float) $lng_raw;

        if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
            return 0;
        }

        $existing = ( $id > 0 ) ? self::get_marker( $id ) : null;

        if ( array_key_exists( 'is_active', $data ) ) {
            $is_active = ! empty( $data['is_active'] ) ? 1 : 0;
        } elseif ( $existing && isset( $existing->is_active ) ) {
            $is_active = (int) $existing->is_active ? 1 : 0;
        } else {
            $is_active = 1;
        }

        if ( array_key_exists( 'icon_source', $data ) ) {
            $icon_source = sanitize_key( (string) $data['icon_source'] );
            if ( ! in_array( $icon_source, array( 'inherit', 'media' ), true ) ) {
                $icon_source = 'inherit';
            }
            $icon_attachment_id = absint( $data['icon_attachment_id'] ?? 0 );
            if ( 'media' !== $icon_source ) {
                $icon_attachment_id = 0;
            }
        } elseif ( $existing ) {
            $icon_source        = isset( $existing->icon_source ) ? sanitize_key( (string) $existing->icon_source ) : 'inherit';
            $icon_attachment_id = isset( $existing->icon_attachment_id ) ? absint( $existing->icon_attachment_id ) : 0;
        } else {
            $icon_source        = 'inherit';
            $icon_attachment_id = 0;
        }

        $table = self::table_markers();
        $row   = array(
            'collection_id'      => $collection_id,
            'lat'                => $lat,
            'lng'                => $lng,
            'text'               => $text,
            'show_on_hover'      => $show_on_hover,
            'show_on_click'      => $show_on_click,
            'show_always'        => $show_always,
            'is_active'          => $is_active,
            'sort_order'         => $sort_order,
            'icon_source'        => $icon_source,
            'icon_attachment_id' => $icon_attachment_id,
            'updated_at'         => $now,
        );

        if ( $existing ) {
            $wpdb->update(
                $table,
                $row,
                array( 'id' => $id ),
                array( '%d', '%f', '%f', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%s' ),
                array( '%d' )
            );

            return $id;
        }

        $row['created_at'] = $now;
        $wpdb->insert(
            $table,
            $row,
            array( '%d', '%f', '%f', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s' )
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * Activa o desactiva un marcador.
     */
    public static function set_marker_active( int $id, bool $active ): bool {
        global $wpdb;

        if ( $id < 1 || ! self::get_marker( $id ) ) {
            return false;
        }

        $updated = $wpdb->update(
            self::table_markers(),
            array(
                'is_active'  => $active ? 1 : 0,
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $id ),
            array( '%d', '%s' ),
            array( '%d' )
        );

        return false !== $updated;
    }

    /**
     * Borra un marcador.
     */
    public static function delete_marker( int $id ): bool {
        global $wpdb;

        if ( $id < 1 ) {
            return false;
        }

        $deleted = $wpdb->delete( self::table_markers(), array( 'id' => $id ), array( '%d' ) );

        return false !== $deleted;
    }

    /**
     * Genera un slug único para colección.
     */
    private static function unique_collection_slug( string $slug, int $exclude_id = 0 ): string {
        global $wpdb;

        $base  = $slug;
        $table = self::table_collections();
        $n     = 2;

        while ( true ) {
            $existing_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE slug = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $slug
                )
            );

            if ( 0 === $existing_id || $existing_id === $exclude_id ) {
                return $slug;
            }

            $slug = $base . '-' . $n;
            ++$n;
        }
    }
}
