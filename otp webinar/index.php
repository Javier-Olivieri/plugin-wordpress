<?php
/**
 * Plugin Name: Maxirest Form OTP Verifier (Backend , Solo para Argentina)
 * Description: Procesamiento en el servidor para validación OTP mediante WP Mail SMTP.
 * Version: 1.2.0
 * Author: Javier Olivieri
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;


// ─────────────────────────────────────────────────────────────────────────────
// 1. PASAR NONCE AL FRONTEND DE FORMA SEGURA
// ─────────────────────────────────────────────────────────────────────────────

add_action('wp_enqueue_scripts', 'maxi_otp_enqueue');

function maxi_otp_enqueue() {
    wp_add_inline_script(
        'jquery',
        'const maxiOtpData = ' . json_encode([
            'nonce' => wp_create_nonce('maxi_otp_nonce')
        ]) . ';',
        'before'
    );
}


// ─────────────────────────────────────────────────────────────────────────────
// 2. GENERAR Y ENVIAR EL OTP (ENVÍO DIRECTO, SIN CRON)
// ─────────────────────────────────────────────────────────────────────────────

add_action('wp_ajax_maxi_enviar_otp',        'maxi_procesar_envio_otp');
add_action('wp_ajax_nopriv_maxi_enviar_otp', 'maxi_procesar_envio_otp');

function maxi_procesar_envio_otp() {

    // Verificar nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maxi_otp_nonce')) {
        wp_send_json_error('Solicitud no autorizada.', 403);
    }

    // Validar email
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    if (!is_email($email)) {
        wp_send_json_error('Correo electrónico no válido.');
    }

    // Rate limiting: máximo 1 envío cada 60 segundos por email
    $rate_key = 'maxi_otp_rate_' . md5($email);
    if (get_transient($rate_key)) {
        wp_send_json_error('Esperá 60 segundos antes de solicitar otro código.');
    }
    set_transient($rate_key, 1, 60);

    // Generar OTP de 6 dígitos
    $otp = rand(100000, 999999);

    // Guardar en base de datos por 15 minutos
    set_transient('maxi_otp_' . md5($email), $otp, 900);

    // Envío directo via wp_mail() → interceptado automáticamente por WP Mail SMTP
    $subject = 'Tu código de verificación - Maxirest';
    $message = "Tu código de verificación es: $otp\n\nEste código expirará en 15 minutos.";
    $enviado = wp_mail($email, $subject, $message);

    if ($enviado) {
        wp_send_json_success('Código enviado. Revisá tu bandeja de entrada.');
    } else {
        wp_send_json_error('El servidor no pudo enviar el correo. Revisá la configuración de WP Mail SMTP.');
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// 3. VERIFICAR EL OTP INTRODUCIDO POR EL USUARIO
// ─────────────────────────────────────────────────────────────────────────────

add_action('wp_ajax_maxi_verificar_otp',        'maxi_procesar_validacion_otp');
add_action('wp_ajax_nopriv_maxi_verificar_otp', 'maxi_procesar_validacion_otp');

function maxi_procesar_validacion_otp() {

    // Verificar nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maxi_otp_nonce')) {
        wp_send_json_error('Solicitud no autorizada.', 403);
    }

    $email          = isset($_POST['email'])  ? sanitize_email($_POST['email'])       : '';
    $codigo_usuario = isset($_POST['codigo']) ? sanitize_text_field($_POST['codigo']) : '';
    $otp_key        = 'maxi_otp_' . md5($email);
    $otp_correcto   = get_transient($otp_key);

    if ($otp_correcto && $codigo_usuario == $otp_correcto) {
        delete_transient($otp_key); // Eliminar para que no sea reutilizable
        wp_send_json_success('Verificado.');
    } else {
        wp_send_json_error('El código es incorrecto o ya caducó.');
    }
}