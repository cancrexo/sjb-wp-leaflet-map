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
        <?php
        $card_class = 'card';
        if ( ! empty( $section['wide'] ) ) {
            $card_class .= ' sjb-info-card-wide';
        }
        ?>
        <div class="<?php echo esc_attr( $card_class ); ?>">
            <div class="card-header">
                <?php echo esc_html( $section['title'] ); ?>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-sm mb-0 sjb-info-table">
                    <tbody>
                        <?php foreach ( $section['rows'] as $row ) : ?>
                            <tr>
                                <th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
                                <td>
                                    <?php if ( ! empty( $row['url'] ) ) : ?>
                                        <a href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $row['value'] ); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html( $row['value'] ); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>
