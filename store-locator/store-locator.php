<?php
/*
Plugin Name: Mapa de distribuidores Maxirest
Description: Muestra un mapa con Distribuidores usando Leaflet y OpenStreetMap.
Version: 1.1
Author: Javier Olivieri
*/

defined('ABSPATH') || exit;

// Cargar scripts y estilos
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
    wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);
    wp_enqueue_style('map-css', plugin_dir_url(__FILE__) . 'css/map.css');
    wp_enqueue_style('custom-css', plugin_dir_url(__FILE__) . 'css/custom.css');
    wp_enqueue_script('map-js', plugin_dir_url(__FILE__) . 'js/map.js', [], null, true);

    // Obtener estilos guardados
    $styles = get_option('store_locator_styles', [
        'markerColor' => '#ff0000',
        'markerType'  => 'default',
    ]);

    wp_localize_script('map-js', 'storeLocatorData', [
        'storesUrl'     => plugin_dir_url(__FILE__) . 'stores.json?v=' . filemtime(plugin_dir_path(__FILE__) . 'stores.json'),
        'styleUrl'      => plugin_dir_url(__FILE__) . 'map-style.json?v=' . filemtime(plugin_dir_path(__FILE__) . 'map-style.json'),
        'textStyleUrl'  => plugin_dir_url(__FILE__) . 'map-text-styles.json?v=' . filemtime(plugin_dir_path(__FILE__) . 'map-text-styles.json'),
        'markerColor'   => $styles['markerColor'],
        'markerType'    => $styles['markerType'],
        'pluginUrl'     => plugin_dir_url(__FILE__)
    ]);
});

// Shortcode para mostrar el mapa y el panel
add_shortcode('store_locator_map', function () {
    ob_start();
    ?>
    <div class="store-locator-wrapper">
        <div id="store-map"></div>
        <div id="store-info-panel">
            <p>Cargando tiendas...</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

// Incluir el panel de administración
require_once plugin_dir_path(__FILE__) . 'admin-panel.php';
