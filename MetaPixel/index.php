<?php
/*
Plugin Name: Pixel Meta (No activar el red)
Description: Inserta el píxel de Meta configurable por sitio. Permite seleccionar páginas, activar eventos y enviar datos mediante la API de Conversiones con deduplicación y Advanced Matching.
Version: 2.0.0
Author: Javier Olivieri
*/

// -----------------------
// Menú en admin
// -----------------------
add_action('admin_menu', function() {
    add_menu_page(
        'Meta Pixel',
        'Meta Pixel',
        'manage_options',
        'meta-pixel-javier',
        'meta_pixel_javier_config_page',
        'dashicons-chart-area',
        60
    );
});

function meta_pixel_javier_config_page() {
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para acceder a esta página.');
    }

    $pages = get_pages();
    $saved_pages = get_option('meta_pixel_javier_selected_pages', []);
    $pixel_id = get_option('meta_pixel_javier_id', '');
    $token = get_option('meta_pixel_javier_token', '');
    $scope = get_option('meta_pixel_javier_scope', 'all');
    $event_lead = get_option('meta_pixel_javier_event_lead', 0);
    $lead_url = get_option('meta_pixel_javier_lead_url', '');
    $event_purchase = get_option('meta_pixel_javier_event_purchase', 0);
    $purchase_url = get_option('meta_pixel_javier_purchase_url', '');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('meta_pixel_javier_save_settings')) {
        update_option('meta_pixel_javier_id', sanitize_text_field($_POST['meta_pixel_javier_id'] ?? ''));
        update_option('meta_pixel_javier_token', sanitize_text_field($_POST['meta_pixel_javier_token'] ?? ''));
        if (isset($_POST['meta_pixel_javier_scope']) && in_array($_POST['meta_pixel_javier_scope'], ['all','selected'])) {
            update_option('meta_pixel_javier_scope', $_POST['meta_pixel_javier_scope']);
            $scope = $_POST['meta_pixel_javier_scope']; 
        }
        update_option('meta_pixel_javier_selected_pages', array_map('intval', $_POST['meta_pixel_javier_selected_pages'] ?? []));
        update_option('meta_pixel_javier_event_lead', isset($_POST['meta_pixel_javier_event_lead']) ? 1 : 0);
        update_option('meta_pixel_javier_lead_url', sanitize_text_field($_POST['meta_pixel_javier_lead_url'] ?? ''));
        update_option('meta_pixel_javier_event_purchase', isset($_POST['meta_pixel_javier_event_purchase']) ? 1 : 0);
        update_option('meta_pixel_javier_purchase_url', sanitize_text_field($_POST['meta_pixel_javier_purchase_url'] ?? ''));
        echo '<div class="updated notice is-dismissible"><p>Configuración guardada correctamente.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Configuración del Meta Pixel</h1>
        <form method="post" action="">
            <?php wp_nonce_field('meta_pixel_javier_save_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="meta_pixel_javier_id">ID del Pixel</label></th>
                    <td>
                        <input name="meta_pixel_javier_id" type="text" id="meta_pixel_javier_id" value="<?php echo esc_attr($pixel_id); ?>" class="regular-text" placeholder="Ej: 1234567890">
                    </td>
                </tr>
                <tr>
                    <th><label for="meta_pixel_javier_token">Access Token de Meta</label></th>
                    <td>
                        <input name="meta_pixel_javier_token" type="text" id="meta_pixel_javier_token" value="<?php echo esc_attr($token); ?>" class="regular-text" placeholder="EAABsb...">
                    </td>
                </tr>
                <tr>
                    <th>Ámbito de medición</th>
                    <td>
                        <label><input type="radio" name="meta_pixel_javier_scope" value="all" <?php checked($scope, 'all'); ?>> Todas las páginas</label><br>
                        <label><input type="radio" name="meta_pixel_javier_scope" value="selected" <?php checked($scope, 'selected'); ?>> Solo páginas seleccionadas</label>
                    </td>
                </tr>
                <tr>
                    <th>Páginas seleccionadas</th>
                    <td id="selected-pages-container">
                        <?php foreach ($pages as $page): ?>
                            <label style="display:block;">
                                <input type="checkbox" name="meta_pixel_javier_selected_pages[]" value="<?php echo esc_attr($page->ID); ?>" <?php checked(in_array($page->ID, (array)$saved_pages)); ?>>
                                <?php echo esc_html($page->post_title); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th>Evento Lead</th>
                    <td>
                        <label><input type="checkbox" name="meta_pixel_javier_event_lead" value="1" <?php checked($event_lead, 1); ?>> Activar evento Lead</label><br><br>
                        <input type="text" name="meta_pixel_javier_lead_url" value="<?php echo esc_attr($lead_url); ?>" placeholder="/gracias-xpress, /gracias-pro" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th>Evento Purchase</th>
                    <td>
                        <label><input type="checkbox" name="meta_pixel_javier_event_purchase" value="1" <?php checked($event_purchase, 1); ?>> Activar evento Purchase</label><br><br>
                        <input type="text" name="meta_pixel_javier_purchase_url" value="<?php echo esc_attr($purchase_url); ?>" placeholder="/compra-xpress, /compra-pro" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th>Test API de Conversiones</th>
                    <td>
                        <button type="button" id="meta-pixel-test-api" class="button button-primary">Enviar evento de prueba</button>
                        <span id="meta-pixel-test-status" style="margin-left:10px;"></span>
                    </td>
                </tr>
            </table>
            <?php submit_button('Guardar configuración'); ?>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const radios = document.querySelectorAll('input[name="meta_pixel_javier_scope"]');
            const container = document.getElementById('selected-pages-container');
            const checkboxes = container.querySelectorAll('input[type="checkbox"]');

            function toggleSelectedPages() {
                const selected = document.querySelector('input[name="meta_pixel_javier_scope"]:checked').value === 'selected';
                checkboxes.forEach(cb => cb.disabled = !selected);
                container.style.opacity = selected ? '1' : '0.5';
            }
            radios.forEach(radio => radio.addEventListener('change', toggleSelectedPages));
            toggleSelectedPages();

            // Test API
            const btn = document.getElementById('meta-pixel-test-api');
            const status = document.getElementById('meta-pixel-test-status');

            btn.addEventListener('click', function() {
                status.textContent = '⏳ Probando API...';
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: 'action=meta_pixel_test_api&_wpnonce=<?php echo wp_create_nonce("meta_pixel_test_api"); ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success){
                        status.textContent = '✅ API de Conversiones ejecutada correctamente';
                        status.style.color = 'green';
                    } else {
                        status.textContent = '❌ Error: ' + (data.data || 'Sin respuesta');
                        status.style.color = 'red';
                    }
                })
                .catch(err => {
                    status.textContent = '❌ Error de conexión';
                    status.style.color = 'red';
                });
            });
        });
        </script>
    </div>
    <?php
}

// -----------------------
// Píxel en <head> con event_id y Advanced Matching
// -----------------------
add_action('wp_head', function() {
    $pixel_id = get_option('meta_pixel_javier_id');
    if (!$pixel_id) return;

    $scope = get_option('meta_pixel_javier_scope', 'all');
    $selected_pages = (array) get_option('meta_pixel_javier_selected_pages', []);
    if ($scope === 'selected' && !is_page($selected_pages)) return;

    $lead_enabled = get_option('meta_pixel_javier_event_lead');
    $lead_urls = array_map('trim', explode(',', get_option('meta_pixel_javier_lead_url')));
    $purchase_enabled = get_option('meta_pixel_javier_event_purchase');
    $purchase_urls = array_map('trim', explode(',', get_option('meta_pixel_javier_purchase_url')));
    $current_path = $_SERVER['REQUEST_URI'];

    $extra_events = '';
    $event_id = '';
    $user_data_js = '{}';

    // Advanced Matching ejemplo
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['telefono']) ? preg_replace('/[^0-9]/', '', $_POST['telefono']) : '';

    if ($email || $phone) {
        $hash_email = $email ? hash('sha256', strtolower($email)) : '';
        $hash_phone = $phone ? hash('sha256', $phone) : '';
        $user_data_js = json_encode(array_filter([
            'em' => $hash_email,
            'ph' => $hash_phone,
        ]));
    }

    if ($lead_enabled && $lead_urls) {
        foreach ($lead_urls as $url) {
            if ($url && strpos($current_path, $url) !== false) {
                $event_id = uniqid('lead_', true);
                $extra_events .= "fbq('track', 'Lead', {}, {eventID: '{$event_id}', userData: {$user_data_js}});\n";
                update_option('meta_pixel_javier_last_event_id', $event_id);
                break;
            }
        }
    }

    if ($purchase_enabled && $purchase_urls && !$event_id) {
        foreach ($purchase_urls as $url) {
            if ($url && strpos($current_path, $url) !== false) {
                $event_id = uniqid('purchase_', true);
                $extra_events .= "fbq('track', 'Purchase', {}, {eventID: '{$event_id}', userData: {$user_data_js}});\n";
                update_option('meta_pixel_javier_last_event_id', $event_id);
                break;
            }
        }
    }

    ?>
    <!-- Meta Pixel Code -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src='https://connect.facebook.net/en_US/fbevents.js';
        s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script');

        fbq('init', '<?php echo esc_js($pixel_id); ?>');
        fbq('track', 'PageView');
        <?php echo $extra_events; ?>
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
             src="https://www.facebook.com/tr?id=<?php echo esc_attr($pixel_id); ?>&ev=PageView&noscript=1"/>
    </noscript>
    <!-- End Meta Pixel Code -->
    <?php
});

// -----------------------
// Enviar eventos a API de Conversiones
// -----------------------
add_action('wp_footer', function () {
    $pixel_id = get_option('meta_pixel_javier_id');
    $token = get_option('meta_pixel_javier_token');
    if (!$pixel_id || !$token) return;

    $lead_enabled = get_option('meta_pixel_javier_event_lead');
    $lead_urls = array_map('trim', explode(',', get_option('meta_pixel_javier_lead_url')));
    $purchase_enabled = get_option('meta_pixel_javier_event_purchase');
    $purchase_urls = array_map('trim', explode(',', get_option('meta_pixel_javier_purchase_url')));
    $current_path = $_SERVER['REQUEST_URI'];

    $event_name = '';
    if ($lead_enabled && $lead_urls) {
        foreach ($lead_urls as $url) {
            if ($url && strpos($current_path, $url) !== false) {
                $event_name = 'Lead';
                break;
            }
        }
    }

    if (!$event_name && $purchase_enabled && $purchase_urls) {
        foreach ($purchase_urls as $url) {
            if ($url && strpos($current_path, $url) !== false) {
                $event_name = 'Purchase';
                break;
            }
        }
    }

    if ($event_name) {
        $event_id = get_option('meta_pixel_javier_last_event_id') ?: uniqid($event_name.'_', true);

        // Advanced Matching para servidor
        $user_data = [];
        if (isset($_POST['email']) && $_POST['email']) {
            $user_data['em'] = hash('sha256', strtolower(trim($_POST['email'])));
        }
        if (isset($_POST['telefono']) && $_POST['telefono']) {
            $user_data['ph'] = hash('sha256', preg_replace('/[^0-9]/', '', $_POST['telefono']));
        }

        $api_url = "https://graph.facebook.com/v18.0/{$pixel_id}/events?access_token={$token}";

        $event_data = [
            'data' => [[
                'event_name' => $event_name,
                'event_time' => time(),
                'action_source' => 'website',
                'event_id' => $event_id,
                'user_data' => $user_data
            ]]
        ];

        wp_remote_post($api_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($event_data),
        ]);
    }
});

// -----------------------
// AJAX Test API
// -----------------------
add_action('wp_ajax_meta_pixel_test_api', function() {
    check_ajax_referer('meta_pixel_test_api');

    $pixel_id = get_option('meta_pixel_javier_id');
    $token = get_option('meta_pixel_javier_token');

    if(!$pixel_id || !$token){
        wp_send_json_error('Faltan Pixel ID o Access Token');
    }

    $api_url = "https://graph.facebook.com/v18.0/{$pixel_id}/events?access_token={$token}";
    $event_data = [
        'data' => [[
            'event_name' => 'TestEvent',
            'event_time' => time(),
            'action_source' => 'website',
            'user_data' => []
        ]]
    ];

    $response = wp_remote_post($api_url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode($event_data),
    ]);

    if(is_wp_error($response)){
        wp_send_json_error($response->get_error_message());
    } else {
        $code = wp_remote_retrieve_response_code($response);
        if($code >= 200 && $code < 300){
            wp_send_json_success();
        } else {
            wp_send_json_error('Código HTTP: '.$code);
        }
    }
});
