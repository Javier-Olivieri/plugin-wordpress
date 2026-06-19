<?php
/**
 * Plugin Name: Maxirest Font Optimizer
 * Description: Bloquea Google Fonts en Astra y optimiza la carga local de Elementor.
 * Version: 1.1
 * Author: Javier Olivieri
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Mata la conexión de Astra con Google Fonts
add_filter( 'astra_google_fonts_selected_fonts', '__return_empty_array' );

// 2. Asegura que el texto sea siempre visible durante la carga (font-display: swap)
add_filter( 'astra_google_fonts_display_property', function() {
    return 'swap';
} );

// 3. Elimina la llamada al CSS de fuentes de Astra del encabezado
add_action( 'wp_enqueue_scripts', function() {
    wp_dequeue_style( 'astra-google-fonts' );
}, 20 );