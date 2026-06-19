<?php
/*
Plugin Name: Meta Pixel (No activar en red, activar en cada sitio)
Description: Inserta el píxel de Meta configurable por sitio. Permite seleccionar páginas, activar eventos y enviar datos mediante la API de Conversiones. Compatible con WordPress Multisite.
Version: 1.5
Author: Javier Olivieri
*/

// Quitar opción de activación en red
add_filter('network_admin_plugin_action_links_' . plugin_basename(__FILE__), function ($actions) {
    unset($actions['activate']);
    $actions['info'] = '<span style="color:#a00;">No se puede activar en red</span>';
    return $actions;
});

// Agregar menú en admin
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
        update_option('meta_pixel_javier_scope', ($_POST['meta_pixel_javier_scope'] ?? 'all') === 'selected' ? 'selected' : 'all');
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
        <h3 style="font-family:arial; text-decoration:underline;"><strong>Uso en multisite:</strong> Este plugin debe activarse y configurarse individualmente en cada sitio de la red.</h3>
        <form method="post" action="">
            <?php wp_nonce_field('meta_pixel_javier_save_settings'); ?>
            <table class="form-table">
                              <tr>
                    <th><label for="meta_pixel_javier_id">ID del Pixel</label></th>
                    <td>
                        <input name="meta_pixel_javier_id" type="text" id="meta_pixel_javier_id" value="<?php echo esc_attr($pixel_id); ?>" class="regular-text" placeholder="Ej: 1234567890">
                        <p class="description">Ingresa el ID del píxel de Meta para este sitio.</p>
                    </td>
                </tr>
                 <tr>
                    <th><label for="meta_pixel_javier_token">Access Token de Meta para la API de Conversiones</label></th>
                    <td>
                        <input name="meta_pixel_javier_token" type="text" id="meta_pixel_javier_token" value="<?php echo esc_attr($token); ?>" class="regular-text" placeholder="EAABsb...">
                        <p class="description">Lo podés obtener en el Administrador de Eventos → API de Conversiones.</p>
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
                    <td>
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
                        <label><input type="checkbox" name="meta_pixel_javier_event_lead" value="1" <?php checked($event_lead, 1); ?>> Activar evento <strong>Lead</strong></label><br><br>
                        <input type="text" name="meta_pixel_javier_lead_url" value="<?php echo esc_attr($lead_url); ?>" placeholder="/gracias-xpress, /gracias-pro" class="regular-text">
                        <p class="description">Ingresá múltiples URLs separadas por coma (ej: <code>/gracias-xpress, /gracias-pro</code>).</p>
                    </td>
                </tr>
                <tr>
                    <th>Evento Purchase</th>
                    <td>
                        <label><input type="checkbox" name="meta_pixel_javier_event_purchase" value="1" <?php checked($event_purchase, 1); ?>> Activar evento <strong>Purchase</strong></label><br><br>
                        <input type="text" name="meta_pixel_javier_purchase_url" value="<?php echo esc_attr($purchase_url); ?>" placeholder="/compra-xpress, /compra-pro" class="regular-text">
                        <p class="description">Ingresá múltiples URLs separadas por coma (ej: <code>/compra-xpress, /compra-pro</code>).</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Guardar configuración'); ?>
        </form>

       <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f9f9f9; border-radius: 12px; border: 1px solid #ddd; max-width: 600px; text-align: center; margin: 0 auto;">
  <h2 style="color: #333;">✅ ¿Ya configuraste todo correctamente?</h2>
  <p style="color: #555; font-size: 16px; line-height: 1.6;">
    Ahora probemos si funciona. Descargá la extensión de Google Chrome 
    <a href="https://chrome.google.com/webstore/detail/meta-pixel-helper/fdgfkebogiimcoedlicjlajpkdmockpc" 
       target="_blank" 
       style="color: #007bff; text-decoration: none; font-weight: bold;">
      Meta Pixel Helper
    </a> 
    para verificar en vivo el funcionamiento del píxel en tu sitio.
  </p>
  <p style="color: #555; font-size: 16px; line-height: 1.6;">
    Podés ver si se activa correctamente cuando cargas la página. ¡Es una forma simple y efectiva de asegurarte de que todo esté en orden!
  </p>
</div>


        <h4>Creador Javier I Olivieri</h4>
    </div>
    <?php
}

// Incluir el píxel en <head>
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

    // ✅ MODIFICADO: múltiples URLs para lead
    if ($lead_enabled && $lead_urls) {
        foreach ($lead_urls as $url) {
            if ($url && strpos($current_path, $url) !== false) {
                $extra_events .= "fbq('track', 'Lead');\n";
                break;
            }
        }
    }

    // ✅ MODIFICADO: múltiples URLs para purchase
    if ($purchase_enabled && $purchase_urls) {
        foreach ($purchase_urls as $url) {
            if ($url && strpos($current_path, $url) !== false) {
                $extra_events .= "fbq('track', 'Purchase');\n";
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

// Enviar a API de conversiones
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

    // ✅ MODIFICADO: múltiples URLs para lead
    if ($lead_enabled && $lead_urls) {
        foreach ($lead_urls as $url) {
            if ($url && strpos($current_path, $url) !== false) {
                $event_name = 'Lead';
                break;
            }
        }
    }

    // ✅ MODIFICADO: múltiples URLs para purchase
    if (!$event_name && $purchase_enabled && $purchase_urls) {
        foreach ($purchase_urls as $url) {
            if ($url && strpos($current_path, $url) !== false) {
                $event_name = 'Purchase';
                break;
            }
        }
    }

    if ($event_name) {
        $api_url = "https://graph.facebook.com/v18.0/{$pixel_id}/events?access_token={$token}";

        $event_data = [
            'data' => [[
                'event_name' => $event_name,
                'event_time' => time(),
                'action_source' => 'website',
                'user_data' => [
                    'client_ip_address' => $_SERVER['REMOTE_ADDR'],
                    'client_user_agent' => $_SERVER['HTTP_USER_AGENT'],
                ]
            ]]
        ];

        wp_remote_post($api_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($event_data),
        ]);
    }
});

