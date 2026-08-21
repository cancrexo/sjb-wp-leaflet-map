<?php
/**
 * Intercambio de colecciones: modelo canónico y exportación (JSON, GeoJSON, KML, KMZ).
 * El mismo payload de build_payload() servirá para importar más adelante.
 *
 * @package sjb-wp-leaflet-map
 * @author  Daniel "Cancrexo" Prol
 * @email   cancrexo@gmail.com
 */

defined( 'ABSPATH' ) || exit;

/**
 * Serialización / deserialización de colecciones entre formatos.
 */
class SJB_WP_LEAFLET_MAP_Exchange {

    /** Versión del esquema del payload canónico (import/export). */
    public const SCHEMA_VERSION = '1.0';

    /** Identificador del formato propio. */
    public const FORMAT_ID = 'sjb-wp-leaflet-map';

    /**
     * Formatos de exportación admitidos.
     *
     * @return list<string>
     */
    public static function formats(): array {
        return array( 'json', 'geojson', 'kml', 'kmz' );
    }

    /**
     * Modelo canónico de una colección (sin IDs de BD).
     * Base común para exportar e importar.
     *
     * @return array<string, mixed>|null
     */
    public static function build_payload( int $collection_id ): ?array {
        $collection = SJB_WP_LEAFLET_MAP_Collections::get_collection( $collection_id );
        if ( ! $collection ) {
            return null;
        }

        $markers = array();
        foreach ( SJB_WP_LEAFLET_MAP_Collections::get_markers( $collection_id ) as $m ) {
            $markers[] = array(
                'lat'          => (float) $m->lat,
                'lng'          => (float) $m->lng,
                'text'         => (string) $m->text,
                'display_mode' => SJB_WP_LEAFLET_MAP_Collections::resolve_marker_display_mode( $m ),
                'is_active'    => ! isset( $m->is_active ) || (int) $m->is_active === 1,
                'sort_order'   => isset( $m->sort_order ) ? (int) $m->sort_order : 0,
                'icon'         => self::icon_meta(
                    isset( $m->icon_source ) ? (string) $m->icon_source : 'inherit',
                    isset( $m->icon_attachment_id ) ? (int) $m->icon_attachment_id : 0
                ),
            );
        }

        return array(
            'format'      => self::FORMAT_ID,
            'version'     => self::SCHEMA_VERSION,
            'exported_at' => gmdate( 'c' ),
            'collection'  => array(
                'name'        => (string) $collection->name,
                'slug'        => (string) $collection->slug,
                'description' => (string) $collection->description,
                'icon'        => self::icon_meta(
                    isset( $collection->icon_source ) ? (string) $collection->icon_source : 'inherit',
                    isset( $collection->icon_attachment_id ) ? (int) $collection->icon_attachment_id : 0
                ),
                'markers'     => $markers,
            ),
        );
    }

    /**
     * Genera el fichero de exportación.
     *
     * @return array{filename:string,mime:string,body:string}|null
     */
    public static function export_file( int $collection_id, string $format ): ?array {
        $format = sanitize_key( $format );
        if ( ! in_array( $format, self::formats(), true ) ) {
            return null;
        }

        $payload = self::build_payload( $collection_id );
        if ( ! $payload ) {
            return null;
        }

        $slug = sanitize_file_name( (string) $payload['collection']['slug'] );
        if ( '' === $slug ) {
            $slug = 'coleccion';
        }

        switch ( $format ) {
            case 'json':
                $body = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
                if ( false === $body ) {
                    return null;
                }
                return array(
                    'filename' => $slug . '.json',
                    'mime'     => 'application/json; charset=utf-8',
                    'body'     => $body,
                );

            case 'geojson':
                $body = wp_json_encode( self::payload_to_geojson( $payload ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
                if ( false === $body ) {
                    return null;
                }
                return array(
                    'filename' => $slug . '.geojson',
                    'mime'     => 'application/geo+json; charset=utf-8',
                    'body'     => $body,
                );

            case 'kml':
                return array(
                    'filename' => $slug . '.kml',
                    'mime'     => 'application/vnd.google-earth.kml+xml; charset=utf-8',
                    'body'     => self::payload_to_kml( $payload ),
                );

            case 'kmz':
                $kmz = self::payload_to_kmz( $payload );
                if ( null === $kmz ) {
                    return null;
                }
                return array(
                    'filename' => $slug . '.kmz',
                    'mime'     => 'application/vnd.google-earth.kmz',
                    'body'     => $kmz,
                );
        }

        return null;
    }

    /**
     * Metadatos de icono portables (URL para otros sitios; attachment_id como pista local).
     *
     * @return array{source:string,attachment_id:int,url:string}
     */
    private static function icon_meta( string $source, int $attachment_id ): array {
        $source = sanitize_key( $source );
        if ( ! in_array( $source, array( 'inherit', 'leaflet', 'media' ), true ) ) {
            $source = 'inherit';
        }

        $url = '';
        if ( 'media' === $source && $attachment_id > 0 ) {
            $full = wp_get_attachment_url( $attachment_id );
            if ( is_string( $full ) && '' !== $full ) {
                $url = $full;
            }
        }

        return array(
            'source'        => $source,
            'attachment_id' => ( 'media' === $source ) ? $attachment_id : 0,
            'url'           => $url,
        );
    }

    /**
     * GeoJSON FeatureCollection a partir del payload canónico.
     *
     * @param array<string, mixed> $payload Payload.
     * @return array<string, mixed>
     */
    private static function payload_to_geojson( array $payload ): array {
        $collection = $payload['collection'];
        $features   = array();

        foreach ( $collection['markers'] as $m ) {
            $features[] = array(
                'type'       => 'Feature',
                'geometry'   => array(
                    'type'        => 'Point',
                    'coordinates' => array( (float) $m['lng'], (float) $m['lat'] ),
                ),
                'properties' => array(
                    'name'         => wp_strip_all_tags( (string) $m['text'] ),
                    'text'         => (string) $m['text'],
                    'display_mode' => (string) $m['display_mode'],
                    'is_active'    => ! empty( $m['is_active'] ),
                    'sort_order'   => (int) $m['sort_order'],
                    'icon'         => $m['icon'],
                    'collection'   => array(
                        'name' => (string) $collection['name'],
                        'slug' => (string) $collection['slug'],
                    ),
                ),
            );
        }

        return array(
            'type'     => 'FeatureCollection',
            'features' => $features,
            'sjb_meta' => array(
                'format'      => self::FORMAT_ID,
                'version'     => self::SCHEMA_VERSION,
                'exported_at' => $payload['exported_at'],
                'collection'  => array(
                    'name'        => (string) $collection['name'],
                    'slug'        => (string) $collection['slug'],
                    'description' => (string) $collection['description'],
                    'icon'        => $collection['icon'],
                ),
            ),
        );
    }

    /**
     * KML a partir del payload canónico.
     *
     * @param array<string, mixed> $payload Payload.
     */
    private static function payload_to_kml( array $payload ): string {
        $collection = $payload['collection'];
        $name       = self::xml_text( (string) $collection['name'] );
        $desc       = self::xml_text( (string) $collection['description'] );

        $placemarks = '';
        foreach ( $collection['markers'] as $m ) {
            $title = wp_strip_all_tags( (string) $m['text'] );
            if ( '' === $title ) {
                $title = sprintf( '(%s, %s)', $m['lat'], $m['lng'] );
            }
            $html = str_replace( ']]>', ']] >', (string) $m['text'] );
            $meta = sprintf(
                'Modo: %s | Activo: %s',
                (string) $m['display_mode'],
                ! empty( $m['is_active'] ) ? 'sí' : 'no'
            );
            $description = $html !== '' ? $html . '<br/><br/>' . esc_html( $meta ) : esc_html( $meta );

            $icon_href = '';
            if ( isset( $m['icon']['url'] ) && is_string( $m['icon']['url'] ) && '' !== $m['icon']['url'] ) {
                $icon_href = $m['icon']['url'];
            } elseif ( isset( $collection['icon']['url'] ) && is_string( $collection['icon']['url'] ) && '' !== $collection['icon']['url'] ) {
                $icon_href = $collection['icon']['url'];
            }

            $style = '';
            if ( '' !== $icon_href ) {
                $style = '<Style><IconStyle><Icon><href>' . self::xml_text( $icon_href ) . '</href></Icon></IconStyle></Style>';
            }

            $placemarks .= sprintf(
                "<Placemark>\n  <name>%s</name>\n  <description><![CDATA[%s]]></description>\n  %s\n  <Point><coordinates>%s,%s,0</coordinates></Point>\n</Placemark>\n",
                self::xml_text( $title ),
                $description,
                $style,
                self::xml_text( (string) $m['lng'] ),
                self::xml_text( (string) $m['lat'] )
            );
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<kml xmlns="http://www.opengis.net/kml/2.2">' . "\n"
            . '<Document>' . "\n"
            . '<name>' . $name . '</name>' . "\n"
            . '<description>' . $desc . '</description>' . "\n"
            . $placemarks
            . '</Document>' . "\n"
            . '</kml>';
    }

    /**
     * KMZ = ZIP con doc.kml.
     *
     * @param array<string, mixed> $payload Payload.
     */
    private static function payload_to_kmz( array $payload ): ?string {
        if ( ! class_exists( 'ZipArchive' ) ) {
            return null;
        }

        $tmp = wp_tempnam( 'sjb-kmz-' );
        if ( ! $tmp ) {
            return null;
        }

        $zip = new ZipArchive();
        if ( true !== $zip->open( $tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
            @unlink( $tmp );
            return null;
        }

        $zip->addFromString( 'doc.kml', self::payload_to_kml( $payload ) );
        $zip->close();

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $body = file_get_contents( $tmp );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
        @unlink( $tmp );

        return ( false === $body ) ? null : $body;
    }

    /**
     * Escapa texto para atributos/contenido XML (fuera de CDATA).
     */
    private static function xml_text( string $value ): string {
        return htmlspecialchars( $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
    }

    /**
     * Lee un archivo subido, detecta formato, valida y devuelve payload canónico + meta.
     *
     * @return array{payload:array,detected:string,is_native:bool,needs_identity:bool,suggested_name:string,suggested_slug:string,markers_count:int}|\WP_Error
     */
    public static function parse_import_file( string $tmp_path, string $original_name ) {
        if ( ! is_readable( $tmp_path ) ) {
            return new WP_Error( 'sjb_import_unreadable', __( 'No se pudo leer el archivo.', 'sjb-wp-leaflet-map' ) );
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $raw = file_get_contents( $tmp_path );
        if ( false === $raw || '' === $raw ) {
            return new WP_Error( 'sjb_import_empty', __( 'El archivo está vacío o no es legible.', 'sjb-wp-leaflet-map' ) );
        }

        $ext = strtolower( pathinfo( $original_name, PATHINFO_EXTENSION ) );
        $detected = self::detect_format( $raw, $ext );

        if ( is_wp_error( $detected ) ) {
            return $detected;
        }

        if ( 'kmz' === $detected ) {
            return new WP_Error(
                'sjb_import_kmz',
                __( 'El KMZ es un ZIP binario. Descomprímelo e importa el archivo KML.', 'sjb-wp-leaflet-map' )
            );
        }

        switch ( $detected ) {
            case 'json':
                $parsed = self::parse_native_json( $raw );
                break;
            case 'geojson':
                $parsed = self::parse_geojson( $raw );
                break;
            case 'kml':
                $parsed = self::parse_kml( $raw );
                break;
            default:
                return new WP_Error( 'sjb_import_format', __( 'Formato no reconocido.', 'sjb-wp-leaflet-map' ) );
        }

        if ( is_wp_error( $parsed ) ) {
            return $parsed;
        }

        $valid = self::validate_payload( $parsed['payload'] );
        if ( is_wp_error( $valid ) ) {
            return $valid;
        }

        $payload = $parsed['payload'];
        $count   = count( $payload['collection']['markers'] );

        return array(
            'payload'         => $payload,
            'detected'        => $parsed['detected'],
            'is_native'       => ! empty( $parsed['is_native'] ),
            'needs_identity'  => ! empty( $parsed['needs_identity'] ),
            'suggested_name'  => (string) $payload['collection']['name'],
            'suggested_slug'  => (string) $payload['collection']['slug'],
            'markers_count'   => $count,
        );
    }

    /**
     * Persiste un payload canónico como nueva colección (+ marcadores).
     *
     * @param array<string, mixed> $payload Payload.
     * @param string|null          $name    Nombre (obligatorio si needs override).
     * @param string|null          $slug    Slug opcional.
     * @return array{collection_id:int,name:string,slug:string,markers:int}|\WP_Error
     */
    public static function import_payload( array $payload, ?string $name = null, ?string $slug = null ) {
        $valid = self::validate_payload( $payload );
        if ( is_wp_error( $valid ) ) {
            return $valid;
        }

        $coll = $payload['collection'];

        $final_name = null !== $name && '' !== trim( $name )
            ? sanitize_text_field( $name )
            : sanitize_text_field( (string) $coll['name'] );
        if ( '' === $final_name ) {
            return new WP_Error( 'sjb_import_name', __( 'El nombre de la colección es obligatorio.', 'sjb-wp-leaflet-map' ) );
        }

        $final_slug = null !== $slug && '' !== trim( (string) $slug )
            ? sanitize_title( (string) $slug )
            : sanitize_title( (string) ( $coll['slug'] ?? '' ) );
        if ( '' === $final_slug ) {
            $final_slug = sanitize_title( $final_name );
        }
        if ( '' === $final_slug ) {
            $final_slug = 'coleccion';
        }

        $final_name = SJB_WP_LEAFLET_MAP_Collections::make_unique_name( $final_name );
        $final_slug = SJB_WP_LEAFLET_MAP_Collections::make_unique_slug( $final_slug );

        $icon_source = 'inherit';
        $icon_att    = 0;
        if ( isset( $coll['icon'] ) && is_array( $coll['icon'] ) ) {
            $icon_source = sanitize_key( (string) ( $coll['icon']['source'] ?? 'inherit' ) );
            if ( ! in_array( $icon_source, array( 'inherit', 'leaflet', 'media' ), true ) ) {
                $icon_source = 'inherit';
            }
            // Solo reutilizar attachment_id si sigue existiendo en este sitio.
            $icon_att = absint( $coll['icon']['attachment_id'] ?? 0 );
            if ( 'media' === $icon_source && $icon_att > 0 && ! wp_attachment_is_image( $icon_att ) ) {
                $icon_source = 'inherit';
                $icon_att    = 0;
            }
            if ( 'media' !== $icon_source ) {
                $icon_att = 0;
            }
        }

        $collection_id = SJB_WP_LEAFLET_MAP_Collections::save_collection(
            array(
                'name'               => $final_name,
                'slug'               => $final_slug,
                'description'        => sanitize_textarea_field( (string) ( $coll['description'] ?? '' ) ),
                'icon_source'        => $icon_source,
                'icon_attachment_id' => $icon_att,
            )
        );

        if ( $collection_id < 1 ) {
            return new WP_Error( 'sjb_import_save', __( 'No se pudo crear la colección.', 'sjb-wp-leaflet-map' ) );
        }

        $saved = 0;
        foreach ( $coll['markers'] as $index => $m ) {
            if ( ! is_array( $m ) ) {
                continue;
            }

            $m_icon_source = 'inherit';
            $m_icon_att    = 0;
            if ( isset( $m['icon'] ) && is_array( $m['icon'] ) ) {
                $m_icon_source = sanitize_key( (string) ( $m['icon']['source'] ?? 'inherit' ) );
                if ( ! in_array( $m_icon_source, array( 'inherit', 'media' ), true ) ) {
                    $m_icon_source = 'inherit';
                }
                $m_icon_att = absint( $m['icon']['attachment_id'] ?? 0 );
                if ( 'media' === $m_icon_source && $m_icon_att > 0 && ! wp_attachment_is_image( $m_icon_att ) ) {
                    $m_icon_source = 'inherit';
                    $m_icon_att    = 0;
                }
                if ( 'media' !== $m_icon_source ) {
                    $m_icon_att = 0;
                }
            }

            $id = SJB_WP_LEAFLET_MAP_Collections::save_marker(
                array(
                    'collection_id'      => $collection_id,
                    'lat'                => $m['lat'] ?? '',
                    'lng'                => $m['lng'] ?? '',
                    'text'               => (string) ( $m['text'] ?? '' ),
                    'display_mode'       => (string) ( $m['display_mode'] ?? 'both' ),
                    'is_active'          => ! empty( $m['is_active'] ),
                    'sort_order'         => isset( $m['sort_order'] ) ? (int) $m['sort_order'] : (int) $index,
                    'icon_source'        => $m_icon_source,
                    'icon_attachment_id' => $m_icon_att,
                )
            );

            if ( $id > 0 ) {
                ++$saved;
            }
        }

        return array(
            'collection_id' => $collection_id,
            'name'          => $final_name,
            'slug'          => $final_slug,
            'markers'       => $saved,
        );
    }

    /**
     * Detecta formato por extensión y contenido.
     *
     * @return string|\WP_Error
     */
    private static function detect_format( string $raw, string $ext ) {
        $ext = strtolower( $ext );
        if ( 'kmz' === $ext ) {
            return 'kmz';
        }
        if ( 'kml' === $ext ) {
            return 'kml';
        }
        if ( in_array( $ext, array( 'geojson', 'json' ), true ) || '' === $ext ) {
            $trim = ltrim( $raw );
            if ( str_starts_with( $trim, '<' ) ) {
                if ( stripos( $trim, '<kml' ) !== false ) {
                    return 'kml';
                }
                return new WP_Error( 'sjb_import_format', __( 'XML no reconocido (¿KML?).', 'sjb-wp-leaflet-map' ) );
            }

            $data = json_decode( $raw, true );
            if ( ! is_array( $data ) ) {
                return new WP_Error( 'sjb_import_json', __( 'JSON inválido.', 'sjb-wp-leaflet-map' ) );
            }
            if ( isset( $data['format'] ) && self::FORMAT_ID === $data['format'] ) {
                return 'json';
            }
            if ( isset( $data['type'] ) && 'FeatureCollection' === $data['type'] ) {
                return 'geojson';
            }
            if ( 'geojson' === $ext ) {
                return new WP_Error( 'sjb_import_geojson', __( 'No parece un GeoJSON FeatureCollection válido.', 'sjb-wp-leaflet-map' ) );
            }
            return new WP_Error(
                'sjb_import_json',
                __( 'JSON no reconocido. Debe ser export del plugin o GeoJSON.', 'sjb-wp-leaflet-map' )
            );
        }

        $trim = ltrim( $raw );
        if ( stripos( $trim, '<kml' ) !== false ) {
            return 'kml';
        }

        return new WP_Error( 'sjb_import_format', __( 'Extensión o contenido no soportado.', 'sjb-wp-leaflet-map' ) );
    }

    /**
     * @return array{payload:array,detected:string,is_native:bool,needs_identity:bool}|\WP_Error
     */
    private static function parse_native_json( string $raw ) {
        $data = json_decode( $raw, true );
        if ( ! is_array( $data ) ) {
            return new WP_Error( 'sjb_import_json', __( 'JSON inválido.', 'sjb-wp-leaflet-map' ) );
        }
        if ( ! isset( $data['format'] ) || self::FORMAT_ID !== $data['format'] ) {
            return new WP_Error( 'sjb_import_json', __( 'No es un JSON de SJB WP Leaflet Map.', 'sjb-wp-leaflet-map' ) );
        }
        if ( ! isset( $data['collection'] ) || ! is_array( $data['collection'] ) ) {
            return new WP_Error( 'sjb_import_json', __( 'Falta el bloque collection.', 'sjb-wp-leaflet-map' ) );
        }

        $payload = self::normalize_incoming_collection( $data['collection'], true );
        if ( is_wp_error( $payload ) ) {
            return $payload;
        }

        return array(
            'payload'        => array(
                'format'      => self::FORMAT_ID,
                'version'     => self::SCHEMA_VERSION,
                'exported_at' => gmdate( 'c' ),
                'collection'  => $payload,
            ),
            'detected'       => 'json',
            'is_native'      => true,
            'needs_identity' => false,
        );
    }

    /**
     * @return array{payload:array,detected:string,is_native:bool,needs_identity:bool}|\WP_Error
     */
    private static function parse_geojson( string $raw ) {
        $data = json_decode( $raw, true );
        if ( ! is_array( $data ) || ( $data['type'] ?? '' ) !== 'FeatureCollection' ) {
            return new WP_Error( 'sjb_import_geojson', __( 'GeoJSON inválido (se espera FeatureCollection).', 'sjb-wp-leaflet-map' ) );
        }

        $features = isset( $data['features'] ) && is_array( $data['features'] ) ? $data['features'] : array();
        $markers  = array();
        $order    = 0;

        foreach ( $features as $feature ) {
            if ( ! is_array( $feature ) ) {
                continue;
            }
            $geom = $feature['geometry'] ?? null;
            if ( ! is_array( $geom ) || ( $geom['type'] ?? '' ) !== 'Point' ) {
                continue;
            }
            $coords = $geom['coordinates'] ?? null;
            if ( ! is_array( $coords ) || count( $coords ) < 2 ) {
                continue;
            }
            $lng = $coords[0];
            $lat = $coords[1];
            if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
                continue;
            }

            $props = isset( $feature['properties'] ) && is_array( $feature['properties'] ) ? $feature['properties'] : array();
            $text  = '';
            if ( isset( $props['text'] ) ) {
                $text = (string) $props['text'];
            } elseif ( isset( $props['name'] ) ) {
                $text = (string) $props['name'];
            } elseif ( isset( $props['description'] ) ) {
                $text = (string) $props['description'];
            }

            $mode = isset( $props['display_mode'] ) ? sanitize_key( (string) $props['display_mode'] ) : 'both';
            if ( ! in_array( $mode, array( 'hover', 'click', 'both', 'always' ), true ) ) {
                $mode = 'both';
            }

            $icon = array(
                'source'        => 'inherit',
                'attachment_id' => 0,
                'url'           => '',
            );
            if ( isset( $props['icon'] ) && is_array( $props['icon'] ) ) {
                $icon = self::normalize_icon_array( $props['icon'] );
            }

            $markers[] = array(
                'lat'          => (float) $lat,
                'lng'          => (float) $lng,
                'text'         => $text,
                'display_mode' => $mode,
                'is_active'    => array_key_exists( 'is_active', $props ) ? ! empty( $props['is_active'] ) : true,
                'sort_order'   => isset( $props['sort_order'] ) ? (int) $props['sort_order'] : $order,
                'icon'         => $icon,
            );
            ++$order;
        }

        if ( ! $markers ) {
            return new WP_Error( 'sjb_import_geojson', __( 'El GeoJSON no contiene puntos válidos.', 'sjb-wp-leaflet-map' ) );
        }

        $name = '';
        $slug = '';
        $desc = '';
        $icon = array(
            'source'        => 'inherit',
            'attachment_id' => 0,
            'url'           => '',
        );
        $has_meta = false;

        if ( isset( $data['sjb_meta']['collection'] ) && is_array( $data['sjb_meta']['collection'] ) ) {
            $has_meta = true;
            $meta_c   = $data['sjb_meta']['collection'];
            $name     = (string) ( $meta_c['name'] ?? '' );
            $slug     = (string) ( $meta_c['slug'] ?? '' );
            $desc     = (string) ( $meta_c['description'] ?? '' );
            if ( isset( $meta_c['icon'] ) && is_array( $meta_c['icon'] ) ) {
                $icon = self::normalize_icon_array( $meta_c['icon'] );
            }
        }

        if ( '' === $name ) {
            $name = 'Importación GeoJSON';
        }
        if ( '' === $slug ) {
            $slug = sanitize_title( $name );
        }

        return array(
            'payload'        => array(
                'format'      => self::FORMAT_ID,
                'version'     => self::SCHEMA_VERSION,
                'exported_at' => gmdate( 'c' ),
                'collection'  => array(
                    'name'        => $name,
                    'slug'        => $slug,
                    'description' => $desc,
                    'icon'        => $icon,
                    'markers'     => $markers,
                ),
            ),
            'detected'       => 'geojson',
            'is_native'      => false,
            'needs_identity' => ! $has_meta,
        );
    }

    /**
     * @return array{payload:array,detected:string,is_native:bool,needs_identity:bool}|\WP_Error
     */
    private static function parse_kml( string $raw ) {
        $previous = libxml_use_internal_errors( true );
        $xml      = simplexml_load_string( $raw );
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );

        if ( false === $xml ) {
            return new WP_Error( 'sjb_import_kml', __( 'KML inválido o mal formado.', 'sjb-wp-leaflet-map' ) );
        }

        $xml->registerXPathNamespace( 'k', 'http://www.opengis.net/kml/2.2' );

        $name_nodes = $xml->xpath( '//k:Document/k:name | //Document/name' );
        $desc_nodes = $xml->xpath( '//k:Document/k:description | //Document/description' );
        $name       = ( $name_nodes && isset( $name_nodes[0] ) ) ? trim( (string) $name_nodes[0] ) : '';
        $desc       = ( $desc_nodes && isset( $desc_nodes[0] ) ) ? trim( (string) $desc_nodes[0] ) : '';

        if ( '' === $name ) {
            $name = 'Importación KML';
        }

        $placemarks = $xml->xpath( '//k:Placemark | //Placemark' );
        if ( ! is_array( $placemarks ) || ! $placemarks ) {
            return new WP_Error( 'sjb_import_kml', __( 'El KML no contiene Placemark.', 'sjb-wp-leaflet-map' ) );
        }

        $markers = array();
        $order   = 0;
        foreach ( $placemarks as $pm ) {
            // El prefijo k registrado en la raíz no se hereda en xpath() de hijos.
            $pm->registerXPathNamespace( 'k', 'http://www.opengis.net/kml/2.2' );
            $coords_nodes = $pm->xpath( './/k:Point/k:coordinates | .//Point/coordinates' );
            if ( ! $coords_nodes || ! isset( $coords_nodes[0] ) ) {
                continue;
            }
            $coords_raw = trim( (string) $coords_nodes[0] );
            $parts      = preg_split( '/\s+/', $coords_raw );
            if ( ! $parts ) {
                continue;
            }
            $first = explode( ',', $parts[0] );
            if ( count( $first ) < 2 || ! is_numeric( $first[0] ) || ! is_numeric( $first[1] ) ) {
                continue;
            }
            $lng = (float) $first[0];
            $lat = (float) $first[1];

            $pm_name = isset( $pm->name ) ? trim( (string) $pm->name ) : '';
            $pm_desc = isset( $pm->description ) ? trim( (string) $pm->description ) : '';
            $text    = '' !== $pm_desc ? $pm_desc : $pm_name;

            $markers[] = array(
                'lat'          => $lat,
                'lng'          => $lng,
                'text'         => $text,
                'display_mode' => 'both',
                'is_active'    => true,
                'sort_order'   => $order,
                'icon'         => array(
                    'source'        => 'inherit',
                    'attachment_id' => 0,
                    'url'           => '',
                ),
            );
            ++$order;
        }

        if ( ! $markers ) {
            return new WP_Error( 'sjb_import_kml', __( 'No se encontraron coordenadas válidas en el KML.', 'sjb-wp-leaflet-map' ) );
        }

        return array(
            'payload'        => array(
                'format'      => self::FORMAT_ID,
                'version'     => self::SCHEMA_VERSION,
                'exported_at' => gmdate( 'c' ),
                'collection'  => array(
                    'name'        => $name,
                    'slug'        => sanitize_title( $name ),
                    'description' => wp_strip_all_tags( $desc ),
                    'icon'        => array(
                        'source'        => 'inherit',
                        'attachment_id' => 0,
                        'url'           => '',
                    ),
                    'markers'     => $markers,
                ),
            ),
            'detected'       => 'kml',
            'is_native'      => false,
            'needs_identity' => true,
        );
    }

    /**
     * Normaliza el bloque collection entrante (JSON propio).
     *
     * @param array<string, mixed> $coll    Datos.
     * @param bool                 $require_name Exigir nombre.
     * @return array<string, mixed>|\WP_Error
     */
    private static function normalize_incoming_collection( array $coll, bool $require_name = true ) {
        $name = sanitize_text_field( (string) ( $coll['name'] ?? '' ) );
        $slug = sanitize_title( (string) ( $coll['slug'] ?? '' ) );
        if ( '' === $slug && '' !== $name ) {
            $slug = sanitize_title( $name );
        }
        if ( $require_name && '' === $name ) {
            return new WP_Error( 'sjb_import_name', __( 'La colección no tiene nombre.', 'sjb-wp-leaflet-map' ) );
        }

        $markers_in = isset( $coll['markers'] ) && is_array( $coll['markers'] ) ? $coll['markers'] : array();
        $markers    = array();
        foreach ( $markers_in as $i => $m ) {
            if ( ! is_array( $m ) ) {
                continue;
            }
            if ( ! isset( $m['lat'], $m['lng'] ) || ! is_numeric( $m['lat'] ) || ! is_numeric( $m['lng'] ) ) {
                continue;
            }
            $lat = (float) $m['lat'];
            $lng = (float) $m['lng'];
            if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
                continue;
            }
            $mode = isset( $m['display_mode'] ) ? sanitize_key( (string) $m['display_mode'] ) : 'both';
            if ( ! in_array( $mode, array( 'hover', 'click', 'both', 'always' ), true ) ) {
                $mode = 'both';
            }
            $markers[] = array(
                'lat'          => $lat,
                'lng'          => $lng,
                'text'         => (string) ( $m['text'] ?? '' ),
                'display_mode' => $mode,
                'is_active'    => array_key_exists( 'is_active', $m ) ? ! empty( $m['is_active'] ) : true,
                'sort_order'   => isset( $m['sort_order'] ) ? (int) $m['sort_order'] : (int) $i,
                'icon'         => self::normalize_icon_array( isset( $m['icon'] ) && is_array( $m['icon'] ) ? $m['icon'] : array() ),
            );
        }

        return array(
            'name'        => $name,
            'slug'        => '' !== $slug ? $slug : 'coleccion',
            'description' => (string) ( $coll['description'] ?? '' ),
            'icon'        => self::normalize_icon_array( isset( $coll['icon'] ) && is_array( $coll['icon'] ) ? $coll['icon'] : array() ),
            'markers'     => $markers,
        );
    }

    /**
     * @param array<string, mixed> $icon Icono.
     * @return array{source:string,attachment_id:int,url:string}
     */
    private static function normalize_icon_array( array $icon ): array {
        $source = sanitize_key( (string) ( $icon['source'] ?? 'inherit' ) );
        if ( ! in_array( $source, array( 'inherit', 'leaflet', 'media' ), true ) ) {
            $source = 'inherit';
        }
        return array(
            'source'        => $source,
            'attachment_id' => absint( $icon['attachment_id'] ?? 0 ),
            'url'           => esc_url_raw( (string) ( $icon['url'] ?? '' ) ),
        );
    }

    /**
     * Valida el payload canónico mínimo.
     *
     * @param array<string, mixed> $payload Payload.
     * @return true|\WP_Error
     */
    private static function validate_payload( array $payload ) {
        if ( ! isset( $payload['collection'] ) || ! is_array( $payload['collection'] ) ) {
            return new WP_Error( 'sjb_import_validate', __( 'Payload sin colección.', 'sjb-wp-leaflet-map' ) );
        }
        $coll = $payload['collection'];
        if ( ! isset( $coll['markers'] ) || ! is_array( $coll['markers'] ) ) {
            return new WP_Error( 'sjb_import_validate', __( 'La colección no tiene lista de marcadores.', 'sjb-wp-leaflet-map' ) );
        }
        if ( ! $coll['markers'] ) {
            return new WP_Error( 'sjb_import_validate', __( 'La colección no contiene marcadores importables.', 'sjb-wp-leaflet-map' ) );
        }
        foreach ( $coll['markers'] as $m ) {
            if ( ! is_array( $m ) || ! isset( $m['lat'], $m['lng'] ) || ! is_numeric( $m['lat'] ) || ! is_numeric( $m['lng'] ) ) {
                return new WP_Error( 'sjb_import_validate', __( 'Hay marcadores con coordenadas inválidas.', 'sjb-wp-leaflet-map' ) );
            }
        }
        return true;
    }
}
