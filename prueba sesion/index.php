<?php
/*
Plugin Name: ComboPoint Button
Plugin URI: https://maxirest.com.ar/
Description: Plugin que crea un shortcode para mostrar un botón que abre ComboPoint en una nueva ventana.
Version: 1.0
Author: Tu Nombre
Author URI: https://tuweb.com
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

function combo_point_button_shortcode() {
    return '<button onclick="window.open(\'https://maxirest.com.ar/combopoint\', \'_blank\', \'width=800,height=600,toolbar=no,menubar=no,scrollbars=yes\')">Abrir ComboPoint</button>';
}
add_shortcode('combo_point_button', 'combo_point_button_shortcode');
