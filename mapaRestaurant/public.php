<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Font Awesome (íconos)



// Shortcode principal del mapa
function mac_shortcode_map() {
    wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet/dist/leaflet.css');
    wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet/dist/leaflet.js', [], null, true);


     
    wp_enqueue_style('mac-mapa', MAC_PLUGIN_URL.'assets/css/mapa.css');
    wp_enqueue_script('mac-mapa', MAC_PLUGIN_URL.'assets/js/mapa.js', ['leaflet'], null, true);

    wp_enqueue_style('mac-roboto', 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

    $points = get_option('mac_points', []);

    $defaults = [
        'pin_color' => '#ff0000',
        'pin_shape' => 'circle',
        'pin_size' => 6,
        'font_size' => 14,
        'font_family' => 'Roboto, Arial, sans-serif',
        'popup_bg' => '#ffffff',
        'popup_text_color' => '#000000',
        'popup_border_color' => '#cccccc',
        'map_width' => '100%',
        'map_height' => 530,
        'map_border_radius' => 30,
    ];
    $estilos_guardados = get_option('mac_estilos', []);
    $estilos = wp_parse_args($estilos_guardados, $defaults);

    wp_localize_script('mac-mapa', 'macOptions', [
        'points' => $points,
        'estilos' => $estilos,
        'pluginUrl' => MAC_PLUGIN_URL
    ]);

    $style = sprintf(
        'width:%s; height:%dpx; border-radius:%dpx; overflow:hidden;',
        esc_attr($estilos['map_width']),
        intval($estilos['map_height']),
        intval($estilos['map_border_radius'])
    );

    return '<div id="mac-map" style="'.$style.'"></div>';
}
add_shortcode('mapa_argentina', 'mac_shortcode_map');
