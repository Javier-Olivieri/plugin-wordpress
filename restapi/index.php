<?php
/**
 * Plugin Name: Saludo API
 * Description: Agrega un endpoint personalizado a la REST API que devuelve un saludo.
 * Version: 1.0
 * Author: Tu Nombre
 */

add_action('rest_api_init', 'mi_plugin_registrar_endpoints');

function mi_plugin_registrar_endpoints() {
    register_rest_route('mi-plugin/v1', '/saludo/', [
        'methods'  => 'GET',
        'callback' => 'mi_plugin_callback_saludo',
        'permission_callback' => '__return_true', // Permitir acceso público
    ]);
}

function mi_plugin_callback_saludo($request) {
    return new WP_REST_Response(['mensaje' => '¡Hola desde la API!'], 200);
}
