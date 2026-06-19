<?php
/**
 * Plugin Name: Mapa Restaurantes
 * Description: Muestra un mapa interactivo de restaurantes en Argentina.
 * Version: 1.1
 * Author: Javier Olivieri
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Definir rutas
define('MAC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Incluir archivos
require_once MAC_PLUGIN_DIR . 'admin.php';
require_once MAC_PLUGIN_DIR . 'public.php';

// Activación
function mac_activate() {
    if ( get_option('mac_points') === false ) {
        update_option('mac_points', []);
    }
}
register_activation_hook(__FILE__, 'mac_activate');
