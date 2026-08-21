<?php
/**
 * Pestaña Marcadores: colecciones y marcadores.
 *
 * @package sjb-wp-leaflet-map
 */

defined( 'ABSPATH' ) || exit;

$collection_id = isset( $_GET['collection_id'] ) ? absint( $_GET['collection_id'] ) : 0;
$collection    = $collection_id ? SJB_WP_LEAFLET_MAP_Collections::get_collection( $collection_id ) : null;

$list_url = add_query_arg(
    array(
        'page' => SJB_WP_LEAFLET_MAP::$slug,
        'tab'  => 'marcadores',
    ),
    admin_url( 'options-general.php' )
);

$confirm_delete_collection = __( '¿Eliminar esta colección y todos sus marcadores?', 'sjb-wp-leaflet-map' );
$confirm_delete_marker     = __( '¿Eliminar este marcador?', 'sjb-wp-leaflet-map' );
?>

<?php if ( ! $collection ) : ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <h2 class="h4 mb-0"><?php esc_html_e( 'Colecciones de marcadores', 'sjb-wp-leaflet-map' ); ?></h2>
        <button
            type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#sjb-modal-collection"
        >
            <?php esc_html_e( 'Nueva colección', 'sjb-wp-leaflet-map' ); ?>
        </button>
    </div>
    <p class="text-muted">
        <?php esc_html_e( 'Crea colecciones reutilizables. Más adelante podrás asociarlas al shortcode del mapa.', 'sjb-wp-leaflet-map' ); ?>
    </p>

    <?php
    $collections  = SJB_WP_LEAFLET_MAP_Collections::get_collections();
    $leaflet_icon = SJB_WP_LEAFLET_MAP::$path2assets . 'vendor/leaflet/images/marker-icon.png';
    if ( $collections ) :
        ?>
        <table class="table table-striped table-bordered align-middle mb-4">
            <thead>
                <tr>
                    <th class="sjb-col-icon"><?php esc_html_e( 'Icono', 'sjb-wp-leaflet-map' ); ?></th>
                    <th><?php esc_html_e( 'Nombre', 'sjb-wp-leaflet-map' ); ?></th>
                    <th><?php esc_html_e( 'ID (slug)', 'sjb-wp-leaflet-map' ); ?></th>
                    <th><?php esc_html_e( 'Descripción', 'sjb-wp-leaflet-map' ); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $collections as $row ) : ?>
                    <?php
                    $markers_url = add_query_arg(
                        array(
                            'page'          => SJB_WP_LEAFLET_MAP::$slug,
                            'tab'           => 'marcadores',
                            'collection_id' => (int) $row->id,
                        ),
                        admin_url( 'options-general.php' )
                    );

                    $resolved_icon = SJB_WP_LEAFLET_MAP_Collections::resolve_map_icon( $row );
                    $icon_url      = ( 'media' === $resolved_icon['source'] && '' !== $resolved_icon['url'] )
                        ? $resolved_icon['url']
                        : $leaflet_icon;

                    $icon_source_raw = isset( $row->icon_source ) ? sanitize_key( (string) $row->icon_source ) : 'inherit';
                    if ( 'leaflet' === $icon_source_raw ) {
                        $icon_title = __( 'Leaflet', 'sjb-wp-leaflet-map' );
                    } elseif ( 'media' === $icon_source_raw ) {
                        $icon_title = __( 'Biblioteca multimedia', 'sjb-wp-leaflet-map' );
                    } else {
                        $icon_title = __( 'Icono global del plugin', 'sjb-wp-leaflet-map' );
                    }
                    ?>
                    <tr>
                        <td class="sjb-col-icon">
                            <span class="sjb-collection-icon-thumb" title="<?php echo esc_attr( $icon_title ); ?>">
                                <img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $icon_title ); ?>">
                            </span>
                        </td>
                        <td><?php echo esc_html( $row->name ); ?></td>
                        <td><code><?php echo esc_html( $row->slug ); ?></code></td>
                        <td><?php echo esc_html( wp_html_excerpt( (string) $row->description, 80, '…' ) ); ?></td>
                        <td class="sjb-col-actions">
                            <div class="sjb-icon-actions d-inline-flex align-items-center gap-1">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-link sjb-icon-btn sjb-icon-btn--edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#sjb-modal-collection"
                                    data-collection-id="<?php echo esc_attr( (string) $row->id ); ?>"
                                    data-collection-name="<?php echo esc_attr( $row->name ); ?>"
                                    data-collection-slug="<?php echo esc_attr( $row->slug ); ?>"
                                    data-collection-description="<?php echo esc_attr( (string) $row->description ); ?>"
                                    data-collection-icon-source="<?php echo esc_attr( isset( $row->icon_source ) ? (string) $row->icon_source : 'inherit' ); ?>"
                                    data-collection-icon-attachment="<?php echo esc_attr( (string) ( isset( $row->icon_attachment_id ) ? (int) $row->icon_attachment_id : 0 ) ); ?>"
                                    data-collection-icon-preview="<?php echo esc_url( ( isset( $row->icon_attachment_id ) && (int) $row->icon_attachment_id > 0 ) ? (string) wp_get_attachment_image_url( (int) $row->icon_attachment_id, 'thumbnail' ) : '' ); ?>"
                                    title="<?php esc_attr_e( 'Editar colección', 'sjb-wp-leaflet-map' ); ?>"
                                    aria-label="<?php esc_attr_e( 'Editar colección', 'sjb-wp-leaflet-map' ); ?>"
                                >
                                    <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                                </button>
                                <a
                                    class="btn btn-sm btn-link sjb-icon-btn sjb-icon-btn--markers"
                                    href="<?php echo esc_url( $markers_url ); ?>"
                                    title="<?php esc_attr_e( 'Editar marcadores', 'sjb-wp-leaflet-map' ); ?>"
                                    aria-label="<?php esc_attr_e( 'Editar marcadores', 'sjb-wp-leaflet-map' ); ?>"
                                >
                                    <span class="dashicons dashicons-location" aria-hidden="true"></span>
                                </a>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-link sjb-icon-btn sjb-icon-btn--export sjb-collection-export"
                                    title="<?php esc_attr_e( 'Exportar', 'sjb-wp-leaflet-map' ); ?>"
                                    aria-label="<?php esc_attr_e( 'Exportar', 'sjb-wp-leaflet-map' ); ?>"
                                    data-collection-id="<?php echo esc_attr( (string) $row->id ); ?>"
                                    data-collection-name="<?php echo esc_attr( $row->name ); ?>"
                                >
                                    <span class="dashicons dashicons-download" aria-hidden="true"></span>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-link sjb-icon-btn sjb-icon-btn--duplicate sjb-collection-duplicate"
                                    title="<?php esc_attr_e( 'Duplicar', 'sjb-wp-leaflet-map' ); ?>"
                                    aria-label="<?php esc_attr_e( 'Duplicar', 'sjb-wp-leaflet-map' ); ?>"
                                    data-collection-id="<?php echo esc_attr( (string) $row->id ); ?>"
                                    data-collection-name="<?php echo esc_attr( $row->name ); ?>"
                                >
                                    <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                                </button>
                                <form
                                    method="post"
                                    action=""
                                    class="sjb-ajax-form d-inline"
                                    data-sjb-action="delete_collection"
                                    data-sjb-confirm="<?php echo esc_attr( $confirm_delete_collection ); ?>"
                                >
                                    <input type="hidden" name="collection_id" value="<?php echo esc_attr( (string) $row->id ); ?>">
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-link sjb-icon-btn sjb-icon-btn--trash"
                                        title="<?php esc_attr_e( 'Borrar', 'sjb-wp-leaflet-map' ); ?>"
                                        aria-label="<?php esc_attr_e( 'Borrar', 'sjb-wp-leaflet-map' ); ?>"
                                    >
                                        <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p class="text-muted"><?php esc_html_e( 'Aún no hay colecciones.', 'sjb-wp-leaflet-map' ); ?></p>
    <?php endif; ?>

    <?php /* Modal: crear / editar colección. */ ?>
    <div
        class="modal fade"
        id="sjb-modal-collection"
        tabindex="-1"
        aria-labelledby="sjb-modal-collection-label"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form
                    method="post"
                    action=""
                    class="sjb-ajax-form sjb-leaflet-collection-form"
                    data-sjb-action="save_collection"
                    id="sjb-form-collection"
                >
                    <div class="modal-header">
                        <h3 class="modal-title h5" id="sjb-modal-collection-label">
                            <?php esc_html_e( 'Nueva colección', 'sjb-wp-leaflet-map' ); ?>
                        </h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Cerrar', 'sjb-wp-leaflet-map' ); ?>"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="collection_id" id="sjb_collection_id" value="0">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="sjb_collection_name"><?php esc_html_e( 'Nombre', 'sjb-wp-leaflet-map' ); ?></label>
                                <input class="form-control" type="text" name="collection_name" id="sjb_collection_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="sjb_collection_slug"><?php esc_html_e( 'ID (slug)', 'sjb-wp-leaflet-map' ); ?></label>
                                <input class="form-control" type="text" name="collection_slug" id="sjb_collection_slug" placeholder="<?php esc_attr_e( 'Opcional: se genera desde el nombre', 'sjb-wp-leaflet-map' ); ?>">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label" for="sjb_collection_description"><?php esc_html_e( 'Descripción', 'sjb-wp-leaflet-map' ); ?></label>
                                <textarea class="form-control" name="collection_description" id="sjb_collection_description" rows="3"></textarea>
                            </div>
                            <div class="col-md-12 mb-0">
                                <fieldset class="sjb-icon-picker" data-sjb-icon-picker>
                                    <legend class="form-label mb-2"><?php esc_html_e( 'Icono de marcador', 'sjb-wp-leaflet-map' ); ?></legend>
                                    <p class="form-text mt-0 mb-2">
                                        <?php esc_html_e( 'Por defecto hereda el icono de la configuración del plugin.', 'sjb-wp-leaflet-map' ); ?>
                                    </p>
                                    <div class="form-check">
                                        <input class="form-check-input sjb-icon-source" type="radio" name="collection_icon_source" id="collection_icon_source_inherit" value="inherit" checked>
                                        <label class="form-check-label" for="collection_icon_source_inherit">
                                            <?php esc_html_e( 'Usar el icono global del plugin', 'sjb-wp-leaflet-map' ); ?>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input sjb-icon-source" type="radio" name="collection_icon_source" id="collection_icon_source_leaflet" value="leaflet">
                                        <label class="form-check-label" for="collection_icon_source_leaflet">
                                            <?php esc_html_e( 'Icono de Leaflet (por defecto)', 'sjb-wp-leaflet-map' ); ?>
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input sjb-icon-source" type="radio" name="collection_icon_source" id="collection_icon_source_media" value="media">
                                        <label class="form-check-label" for="collection_icon_source_media">
                                            <?php esc_html_e( 'Imagen de la biblioteca multimedia', 'sjb-wp-leaflet-map' ); ?>
                                        </label>
                                    </div>
                                    <div class="sjb-icon-media-row d-none" data-sjb-icon-media-row>
                                        <input type="hidden" name="collection_icon_attachment" class="sjb-icon-attachment-id" id="sjb_collection_icon_attachment" value="0">
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <div class="sjb-icon-preview" data-sjb-icon-preview>
                                                <span class="text-muted small"><?php esc_html_e( 'Ninguna imagen seleccionada', 'sjb-wp-leaflet-map' ); ?></span>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-outline-primary btn-sm sjb-icon-select">
                                                    <?php esc_html_e( 'Seleccionar imagen', 'sjb-wp-leaflet-map' ); ?>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm sjb-icon-clear d-none">
                                                    <?php esc_html_e( 'Quitar', 'sjb-wp-leaflet-map' ); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sjb-icon-leaflet-preview d-none mt-2" data-sjb-icon-leaflet-preview>
                                        <img src="<?php echo esc_url( SJB_WP_LEAFLET_MAP::$path2assets . 'vendor/leaflet/images/marker-icon.png' ); ?>" alt="" width="25" height="41">
                                        <span class="text-muted small ms-2"><?php esc_html_e( 'Vista previa del pin de Leaflet', 'sjb-wp-leaflet-map' ); ?></span>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <?php esc_html_e( 'Cancelar', 'sjb-wp-leaflet-map' ); ?>
                        </button>
                        <button type="submit" class="btn btn-primary" id="sjb-form-collection-submit">
                            <?php esc_html_e( 'Crear colección', 'sjb-wp-leaflet-map' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php else : ?>
    <?php
    $markers = SJB_WP_LEAFLET_MAP_Collections::get_markers( (int) $collection->id );

    /**
     * Opciones del select «Mostrar texto».
     *
     * @return array<string, string>
     */
    $marker_mode_options = static function (): array {
        return array(
            'both'   => __( 'Hover y clic', 'sjb-wp-leaflet-map' ),
            'hover'  => __( 'Solo hover (tooltip)', 'sjb-wp-leaflet-map' ),
            'click'  => __( 'Solo clic (popup)', 'sjb-wp-leaflet-map' ),
            'always' => __( 'Siempre visible', 'sjb-wp-leaflet-map' ),
        );
    };

    /**
     * Resuelve el modo de visualización de un marcador.
     *
     * @param object $m Marcador.
     */
    $resolve_marker_mode = static function ( object $m ): string {
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
    };

    $mode_options = $marker_mode_options();
    ?>

    <p class="mb-3">
        <a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'Volver a colecciones', 'sjb-wp-leaflet-map' ); ?></a>
    </p>

    <h2 class="h4 mb-2"><?php echo esc_html( sprintf( /* translators: %s: collection name */ __( 'Colección: %s', 'sjb-wp-leaflet-map' ), $collection->name ) ); ?></h2>

    <p class="form-text mb-3 <?php echo $markers ? '' : 'd-none'; ?>" id="sjb-markers-help">
        <?php esc_html_e( 'Latitud y longitud son obligatorias. HTML permitido en el texto: strong/b, u, br, a (enlaces). Los cambios válidos se guardan automáticamente.', 'sjb-wp-leaflet-map' ); ?>
    </p>

    <table
        class="table table-bordered align-middle mb-2<?php echo $markers ? '' : ' d-none'; ?>"
        id="sjb-markers-table"
        data-collection-id="<?php echo esc_attr( (string) $collection->id ); ?>"
        data-confirm-delete="<?php echo esc_attr( $confirm_delete_marker ); ?>"
    >
        <thead>
            <tr>
                <th style="width: 8rem;"><?php esc_html_e( 'Latitud', 'sjb-wp-leaflet-map' ); ?></th>
                <th style="width: 8rem;"><?php esc_html_e( 'Longitud', 'sjb-wp-leaflet-map' ); ?></th>
                <th><?php esc_html_e( 'Texto', 'sjb-wp-leaflet-map' ); ?></th>
                <th style="width: 12rem;"><?php esc_html_e( 'Mostrar texto', 'sjb-wp-leaflet-map' ); ?></th>
                <th class="text-center" style="width: 4.5rem;"><?php esc_html_e( 'Estado', 'sjb-wp-leaflet-map' ); ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody id="sjb-markers-tbody">
            <?php foreach ( $markers as $m ) : ?>
                <?php
                $mode      = $resolve_marker_mode( $m );
                $is_active = ! isset( $m->is_active ) || (int) $m->is_active === 1;
                ?>
                <tr class="sjb-marker-row" data-marker-id="<?php echo esc_attr( (string) $m->id ); ?>" data-active="<?php echo $is_active ? '1' : '0'; ?>">
                    <td>
                        <input
                            type="text"
                            class="form-control form-control-sm sjb-marker-lat"
                            value="<?php echo esc_attr( (string) $m->lat ); ?>"
                            inputmode="decimal"
                            autocomplete="off"
                        >
                    </td>
                    <td>
                        <input
                            type="text"
                            class="form-control form-control-sm sjb-marker-lng"
                            value="<?php echo esc_attr( (string) $m->lng ); ?>"
                            inputmode="decimal"
                            autocomplete="off"
                        >
                    </td>
                    <td>
                        <textarea class="form-control form-control-sm sjb-marker-text" rows="2"><?php echo esc_textarea( (string) $m->text ); ?></textarea>
                    </td>
                    <td>
                        <select class="form-select form-select-sm sjb-marker-mode">
                            <?php foreach ( $mode_options as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $mode, $value ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="sjb-col-status text-center">
                        <button
                            type="button"
                            class="btn btn-sm btn-link sjb-icon-btn sjb-marker-status <?php echo $is_active ? 'sjb-icon-btn--active' : 'sjb-icon-btn--inactive'; ?>"
                            title="<?php echo $is_active ? esc_attr__( 'Activo (clic para desactivar)', 'sjb-wp-leaflet-map' ) : esc_attr__( 'Inactivo (clic para activar)', 'sjb-wp-leaflet-map' ); ?>"
                            aria-label="<?php echo $is_active ? esc_attr__( 'Activo (clic para desactivar)', 'sjb-wp-leaflet-map' ) : esc_attr__( 'Inactivo (clic para activar)', 'sjb-wp-leaflet-map' ); ?>"
                        >
                            <span class="dashicons <?php echo $is_active ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>" aria-hidden="true"></span>
                        </button>
                    </td>
                    <td class="sjb-col-actions">
                        <div class="sjb-icon-actions d-inline-flex align-items-center gap-1">
                            <button
                                type="button"
                                class="btn btn-sm btn-link sjb-icon-btn sjb-icon-btn--duplicate sjb-marker-duplicate"
                                title="<?php esc_attr_e( 'Duplicar', 'sjb-wp-leaflet-map' ); ?>"
                                aria-label="<?php esc_attr_e( 'Duplicar', 'sjb-wp-leaflet-map' ); ?>"
                            >
                                <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-link sjb-icon-btn sjb-icon-btn--trash sjb-marker-delete"
                                title="<?php esc_attr_e( 'Eliminar marcador', 'sjb-wp-leaflet-map' ); ?>"
                                aria-label="<?php esc_attr_e( 'Eliminar marcador', 'sjb-wp-leaflet-map' ); ?>"
                            >
                                <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="text-muted text-center sjb-markers-empty mb-2 <?php echo $markers ? 'd-none' : ''; ?>" id="sjb-markers-empty">
        <?php esc_html_e( 'Todavía no hay ningún marcador.', 'sjb-wp-leaflet-map' ); ?>
    </p>

    <div class="sjb-markers-add-wrap">
        <button type="button" class="btn btn-primary sjb-btn-add-marker" id="sjb-add-marker">
            <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
            <?php esc_html_e( 'Añadir marcador', 'sjb-wp-leaflet-map' ); ?>
        </button>
    </div>

    <template id="sjb-marker-row-template">
        <tr class="sjb-marker-row" data-marker-id="0" data-active="1">
            <td>
                <input type="text" class="form-control form-control-sm sjb-marker-lat" value="" inputmode="decimal" autocomplete="off">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm sjb-marker-lng" value="" inputmode="decimal" autocomplete="off">
            </td>
            <td>
                <textarea class="form-control form-control-sm sjb-marker-text" rows="2"></textarea>
            </td>
            <td>
                <select class="form-select form-select-sm sjb-marker-mode">
                    <?php foreach ( $mode_options as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, 'both' ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="sjb-col-status text-center">
                <button
                    type="button"
                    class="btn btn-sm btn-link sjb-icon-btn sjb-marker-status sjb-icon-btn--active"
                    title="<?php esc_attr_e( 'Activo (clic para desactivar)', 'sjb-wp-leaflet-map' ); ?>"
                    aria-label="<?php esc_attr_e( 'Activo (clic para desactivar)', 'sjb-wp-leaflet-map' ); ?>"
                >
                    <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                </button>
            </td>
            <td class="sjb-col-actions">
                <div class="sjb-icon-actions d-inline-flex align-items-center gap-1">
                    <button
                        type="button"
                        class="btn btn-sm btn-link sjb-icon-btn sjb-icon-btn--duplicate sjb-marker-duplicate"
                        title="<?php esc_attr_e( 'Duplicar', 'sjb-wp-leaflet-map' ); ?>"
                        aria-label="<?php esc_attr_e( 'Duplicar', 'sjb-wp-leaflet-map' ); ?>"
                    >
                        <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-link sjb-icon-btn sjb-icon-btn--trash sjb-marker-delete"
                        title="<?php esc_attr_e( 'Eliminar marcador', 'sjb-wp-leaflet-map' ); ?>"
                        aria-label="<?php esc_attr_e( 'Eliminar marcador', 'sjb-wp-leaflet-map' ); ?>"
                    >
                        <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                    </button>
                </div>
            </td>
        </tr>
    </template>
<?php endif; ?>
