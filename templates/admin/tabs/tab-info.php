<?php
/**
 * Pestaña Info: plugin y entorno del servidor.
 *
 * @package sjb-wp-leaflet-map
 */

defined( 'ABSPATH' ) || exit;

$sections = SJB_WP_LEAFLET_MAP::get_system_info();
?>
<h2 class="h4"><?php esc_html_e( 'Información', 'sjb-wp-leaflet-map' ); ?></h2>
<p class="text-muted">
    <?php esc_html_e( 'Datos del plugin y del entorno (servidor, PHP, base de datos y versiones). Útil para soporte y diagnóstico.', 'sjb-wp-leaflet-map' ); ?>
</p>

<div class="sjb-config-cards sjb-info-cards">
    <?php foreach ( $sections as $section ) : ?>
        <div class="card">
            <div class="card-header">
                <?php echo esc_html( $section['title'] ); ?>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-sm mb-0 sjb-info-table">
                    <tbody>
                        <?php foreach ( $section['rows'] as $row ) : ?>
                            <tr>
                                <th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
                                <td><?php echo esc_html( $row['value'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>
