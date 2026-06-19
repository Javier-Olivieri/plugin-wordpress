<?php
/**
 * Plugin Name: Maxirest Browser Cache Fix
 * Description: Fuerza el Cache de Navegador enviando cabeceras de expiracion via PHP.
 * Version: 1.0
 * Author: Javier Olivieri
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function mx_enviar_cabeceras_cache() {
    // 31536000 segundos = 1 año
    $segundos_un_anio = 31536000; 
    
    // Solo aplicamos esto si no estamos en el panel de administracion
    if ( !is_admin() ) {
        header("Cache-Control: public, max-age=$segundos_un_anio, immutable");
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $segundos_un_anio) . " GMT");
        header("Pragma: cache");
    }
}

// Usamos el hook 'send_headers' para inyectar la instruccion antes de que cargue la pagina
add_action('send_headers', 'mx_enviar_cabeceras_cache');