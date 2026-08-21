<?php
/**
 * Pantalla de ajustes del plugin (tres pestañas).
 *
 * @package sjb-wp-leaflet-map
 *
 * @var array<string,mixed> $options
 * @var string              $active_tab
 */

defined( 'ABSPATH' ) || exit;

$is_config     = ( 'configuracion' === $active_tab );
$is_marcadores = ( 'marcadores' === $active_tab );
$is_info       = ( 'info' === $active_tab );
?>
<div class="wrap sjb-leaflet-admin">
    <h1><?php echo esc_html( SJB_WP_LEAFLET_MAP::$title ); ?></h1>

    <ul class="nav nav-tabs" id="sjbLeafletTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link<?php echo $is_config ? ' active' : ''; ?>" id="tab-configuracion-btn" href="<?php echo esc_url( admin_url( 'options-general.php?page=' . SJB_WP_LEAFLET_MAP::$slug . '&tab=configuracion' ) ); ?>" role="tab" aria-controls="tab-configuracion" aria-selected="<?php echo $is_config ? 'true' : 'false'; ?>">
                <?php esc_html_e( 'Configuración', 'sjb-wp-leaflet-map' ); ?>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link<?php echo $is_marcadores ? ' active' : ''; ?>" id="tab-marcadores-btn" href="<?php echo esc_url( admin_url( 'options-general.php?page=' . SJB_WP_LEAFLET_MAP::$slug . '&tab=marcadores' ) ); ?>" role="tab" aria-controls="tab-marcadores" aria-selected="<?php echo $is_marcadores ? 'true' : 'false'; ?>">
                <?php esc_html_e( 'Marcadores', 'sjb-wp-leaflet-map' ); ?>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link<?php echo $is_info ? ' active' : ''; ?>" id="tab-info-btn" href="<?php echo esc_url( admin_url( 'options-general.php?page=' . SJB_WP_LEAFLET_MAP::$slug . '&tab=info' ) ); ?>" role="tab" aria-controls="tab-info" aria-selected="<?php echo $is_info ? 'true' : 'false'; ?>">
                <?php esc_html_e( 'Info', 'sjb-wp-leaflet-map' ); ?>
            </a>
        </li>
    </ul>

    <div class="tab-content sjb-leaflet-tab-content" id="sjbLeafletTabContent">
        <?php if ( $is_config ) : ?>
            <div class="tab-pane fade show active" id="tab-configuracion" role="tabpanel" aria-labelledby="tab-configuracion-btn" tabindex="0">
                <?php require SJB_WP_LEAFLET_MAP::$plugindir . 'templates/admin/tabs/tab-configuracion.php'; ?>
            </div>
        <?php elseif ( $is_marcadores ) : ?>
            <div class="tab-pane fade show active" id="tab-marcadores" role="tabpanel" aria-labelledby="tab-marcadores-btn" tabindex="0">
                <?php require SJB_WP_LEAFLET_MAP::$plugindir . 'templates/admin/tabs/tab-marcadores.php'; ?>
            </div>
        <?php else : ?>
            <div class="tab-pane fade show active" id="tab-info" role="tabpanel" aria-labelledby="tab-info-btn" tabindex="0">
                <?php require SJB_WP_LEAFLET_MAP::$plugindir . 'templates/admin/tabs/tab-info.php'; ?>
            </div>
        <?php endif; ?>

        <p class="sjb-leaflet-author-footnote">
            Daniel &quot;Cancrexo&quot; Prol · SJB Dixital ·
            <a href="mailto:cancrexo@gmail.com">cancrexo@gmail.com</a>
            ·
            <a href="https://www.sjbdixital.es" target="_blank" rel="noopener noreferrer">sjbdixital.es</a>
        </p>
    </div>
</div>
