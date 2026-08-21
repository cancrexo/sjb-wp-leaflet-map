<?php
/**
 * Pantalla de ajustes del plugin (tres pestañas).
 *
 * @package sjb-wp-leaflet-map
 *
 * @var bool                $updated
 * @var array<string,mixed> $options
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap sjb-leaflet-admin">
    <h1><?php echo esc_html( SJB_WP_LEAFLET_MAP::$title ); ?></h1>

    <?php if ( $updated ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Ajustes guardados.', 'sjb-wp-leaflet-map' ); ?></p>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs" id="sjbLeafletTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-configuracion-btn" data-bs-toggle="tab" data-bs-target="#tab-configuracion" type="button" role="tab" aria-controls="tab-configuracion" aria-selected="true">
                <?php esc_html_e( 'Configuración', 'sjb-wp-leaflet-map' ); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-marcadores-btn" data-bs-toggle="tab" data-bs-target="#tab-marcadores" type="button" role="tab" aria-controls="tab-marcadores" aria-selected="false">
                <?php esc_html_e( 'Marcadores', 'sjb-wp-leaflet-map' ); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-info-btn" data-bs-toggle="tab" data-bs-target="#tab-info" type="button" role="tab" aria-controls="tab-info" aria-selected="false">
                <?php esc_html_e( 'Info', 'sjb-wp-leaflet-map' ); ?>
            </button>
        </li>
    </ul>

    <div class="tab-content sjb-leaflet-tab-content" id="sjbLeafletTabContent">
        <div class="tab-pane fade show active" id="tab-configuracion" role="tabpanel" aria-labelledby="tab-configuracion-btn" tabindex="0">
            <?php require SJB_WP_LEAFLET_MAP::$plugindir . 'templates/admin/tabs/tab-configuracion.php'; ?>
        </div>
        <div class="tab-pane fade" id="tab-marcadores" role="tabpanel" aria-labelledby="tab-marcadores-btn" tabindex="0">
            <?php require SJB_WP_LEAFLET_MAP::$plugindir . 'templates/admin/tabs/tab-marcadores.php'; ?>
        </div>
        <div class="tab-pane fade" id="tab-info" role="tabpanel" aria-labelledby="tab-info-btn" tabindex="0">
            <?php require SJB_WP_LEAFLET_MAP::$plugindir . 'templates/admin/tabs/tab-info.php'; ?>
        </div>
    </div>
</div>
