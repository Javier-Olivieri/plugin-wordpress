<?php
/*
Plugin Name: CAPI Lead Sender Custom Multisite (Activado en sitio específico)
Description: Envía eventos Lead por la API de Conversiones con deduplicación, Coincidencias Avanzadas (AM) de email/teléfono y fbp/fbc. Versión corregida para Meta.
Version: 1.6.5 // Nueva versión
Author: Javier Olivieri 
*/

// CONFIGURACIÓN GLOBAL
// Reemplaza con tu ID de Píxel real
$CAPI_PIXEL_ID = '775710753109401';
// ASEGÚRATE DE USAR TU ACCESS TOKEN ORIGINAL AQUÍ (Debe estar en una sola línea)
$CAPI_ACCESS_TOKEN = 'EABR4AVQCMZCoBQBVcfEdZCvyZB9XzMDkR2e7t96LHsbmyVQ0UPb9A35DVygrHt9QecNdIjG5O0zawnDOi38PscHGRVc6RMPnjbuNXXfY8VZCIRglDnYNUgcl4lB3VUCGZAq7JvLfFMLxsB5WfLdZC8l0ROc6PuqxZAIK1TJRkYBkfUD26ON9wH6VwXWPZBCi4jwGNQZDZD';

// URLs que disparan Lead
function capi_lead_urls() {
    return [
        'https://maxirest.com.ar/contacto-enviado/',
        'https://maxirest.com.ar/point-enviado/',
        'https://maxirest.com.ar/pro-enviado/',
        'https://maxirest.com.ar/xpress-enviado/',
        'https://maxirest.com.ar/activacion-prueba-gratuita/',
        'https://maxirest.com.ar/enviado-combo-point/',
        'https://maxirest.com.ar/enviado-combo-pro/',
        'https://maxirest.com.ar/enviado-combo-xpress/'
    ];
}

/**
 * Inyecta el script JS para disparar el evento del píxel, capturar AM, llamar al CAPI y limpiar la URL.
 */
add_action('wp_footer', function() {
    // Lógica de validación de URL para inyectar JS solo en páginas de agradecimiento
    
    // Obtener la URL completa del request
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    $parsed_current = parse_url($current_url);
    // Corrección de URL: Extraemos la ruta (path) de la URL actual y nos aseguramos de que termine en barra
    $current_path = trailingslashit(isset($parsed_current['path']) ? $parsed_current['path'] : '/');

    $is_thank_you_page = false;

    // Recorremos las URLs válidas
    foreach (capi_lead_urls() as $u) {
        $parsed_list = parse_url($u);
        // Extraemos la ruta (path) de la URL de la lista y nos aseguramos de que termine en barra
        $list_path = trailingslashit(isset($parsed_list['path']) ? $parsed_list['path'] : '/');

        // Solo comparamos el path, ignorando protocolo, host y query.
        if ($current_path === $list_path) {
            $is_thank_you_page = true;
            break;
        }
    }

    if (!$is_thank_you_page) {
        return; 
    }
?>
<script>
(function($) {
    $(document).ready(function() {
        if (typeof $ !== 'undefined') {
            
            function generateUUID() {
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                    var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
                    return v.toString(16);
                });
            }

            // Función auxiliar para obtener parámetros de la URL
            function getUrlParameter(name) {
                name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
                var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
                var results = regex.exec(location.search);
                return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
            }
            
            let custom_eid = generateUUID();
            let currentUrl = encodeURIComponent(window.location.href);
            
            // Captura de FBP y FBC (Cookies)
            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
                return '';
            }

            let fbp = getCookie('_fbp');
            let fbc = getCookie('_fbc');
            
            let user_id_param = '';

            if (fbc) {
                user_id_param += '&fbc=' + fbc;
            }
            if (fbp) {
                user_id_param += '&fbp=' + fbp;
            }
            
            // Captura de Email y Teléfono para Advanced Matching (AM) desde la URL
            let captured_email = getUrlParameter('am_email');
            let captured_phone = getUrlParameter('am_phone'); 
            let am_params = '';

            if (captured_email) {
                am_params += '&am_email=' + captured_email.toLowerCase().trim();
            }
            
            if (captured_phone) {
                am_params += '&am_phone=' + captured_phone; 
            }

            // Corrección de Precio: Eliminamos el valor y divisa fijos.
            if (typeof fbq === 'function') {
                fbq('track', 'Lead', 
                    {}, // Objeto de datos vacío para un Lead sin valor monetario
                    { eventID: custom_eid }
                );
            }
            
            // Enviamos el mismo Event ID + fbp/fbc + AM_EMAIL/AM_PHONE al CAPI.
            if (custom_eid) {
                let capi_url = '/wp-json/capi/v1/lead?eid=' + custom_eid + '&ref_url=' + currentUrl + user_id_param + am_params;
                
                fetch(capi_url)
                    .then(r => r.json())
                    .then(data => {
                        console.log('CAPI Respuesta del Servidor:', data);
                    })
                    .catch(console.error);
            }
            
            // ** CÓDIGO DE LIMPIEZA DE URL (SOLUCIÓN VISUAL) **
            if (window.location.search.includes('am_email=') || window.location.search.includes('am_phone=')) {
                let new_url = window.location.origin + window.location.pathname;
                let search_params = new URLSearchParams(window.location.search);

                search_params.delete('am_email');
                search_params.delete('am_phone');
                
                let new_search = search_params.toString();

                if (new_search) {
                    new_url += '?' + new_search;
                }
                
                window.history.replaceState(null, '', new_url);
            }
            
        } 
    });
})(jQuery);
</script>
<?php
});

// Registrar endpoint REST
add_action('rest_api_init', function() {
    register_rest_route('capi/v1', '/lead', [
        'methods' => 'GET',
        'callback' => 'capi_send_lead_event'
    ]);
});

/**
 * Función principal de envío CAPI con FBP/FBC y Advanced Matching de Email/Teléfono.
 */
function capi_send_lead_event($data) {
    global $CAPI_PIXEL_ID, $CAPI_ACCESS_TOKEN;

    $full_current_url = sanitize_url($_GET['ref_url'] ?? '');
    
    if (empty($full_current_url)) {
        return ['status' => 'ignored', 'reason' => 'missing_ref_url'];
    }
    
    // 2. Preparación de Datos
    $event_id = sanitize_text_field($_GET['eid'] ?? '');
    
    // Corrección de IP
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Captura FBP y FBC
    $fbp = sanitize_text_field($_GET['fbp'] ?? '');
    
    // ⭐ CORRECCIÓN PARA EL ERROR FBC: Capturamos el valor de la forma más RAW.
    $fbc = isset($_GET['fbc']) ? wp_unslash($_GET['fbc']) : ''; 

    // ** CAPTURA Y HASHEO DE EMAIL Y TELÉFONO (Advanced Matching) **
    $am_email = sanitize_email($_GET['am_email'] ?? '');
    $am_phone = sanitize_text_field($_GET['am_phone'] ?? ''); 
    
    $hashed_email = '';
    $hashed_phone = '';
    
    // Procesa Email
    if (!empty($am_email)) {
        $normalized_email = strtolower(trim($am_email)); 
        $hashed_email = hash('sha256', $normalized_email); 
    }

    // Procesa Teléfono (Mejora la coincidencia avanzada)
    if (!empty($am_phone)) {
        $normalized_phone = preg_replace('/[^0-9]/', '', $am_phone); 
        $hashed_phone = hash('sha256', $normalized_phone); 
    }

    // Construcción del user_data
    $user_data = [
        'client_ip_address' => $ip, 
        'client_user_agent' => $ua
    ];
    
    if (!empty($hashed_email)) {
        $user_data['em'] = $hashed_email; 
    }
    if (!empty($hashed_phone)) {
        $user_data['ph'] = $hashed_phone; 
    }

    if (!empty($fbp)) {
        $user_data['fbp'] = $fbp;
    }
    if (!empty($fbc)) {
        $user_data['fbc'] = $fbc;
    }

    // Corrección de Precio: Eliminamos el bloque custom_data con valor/divisa fijos.
    $payload = [
        'data' => [[
            'event_name' => 'Lead',
            'event_time' => time(),
            'event_id' => $event_id, 
            'action_source' => 'website',
            'user_data' => $user_data,
        ]]
    ];

    $url = "https://graph.facebook.com/v18.0/{$CAPI_PIXEL_ID}/events?access_token={$CAPI_ACCESS_TOKEN}";

    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode($payload)
    ]);
    
    // 4. Manejo de Errores y Respuesta
    if (is_wp_error($response)) {
        return [
            'status' => 'error',
            'reason' => 'WP_Remote_Error',
            'message' => $response->get_error_message()
        ];
    }
    
    $body = wp_remote_retrieve_body($response);
    $meta_response = json_decode($body, true);
    
    if (isset($meta_response['error'])) {
        return [
            'status' => 'error',
            'reason' => 'Meta_API_Error',
            'message' => $meta_response['error']['message'] ?? 'Error desconocido'
        ];
    }

    return [
        'status' => 'sent', 
        'payload' => $meta_response
    ];
}