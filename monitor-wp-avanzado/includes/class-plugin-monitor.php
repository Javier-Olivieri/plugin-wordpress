<?php
class PluginMonitor {
    private static $alert_email = 'javier.olivieri@maxisistemas.com.ar';

    public static function init() {
        add_action('admin_init', [self::class, 'check_plugins']);
        set_error_handler([self::class, 'handle_php_errors']);
    }

    public static function check_plugins() {
        if (!current_user_can('manage_options')) return;

        // 🔹 Plugins de red (solo en el sitio principal o administrador de la red)
        $network_plugins = [];
        if (is_multisite()) {
            $network_plugins = get_site_option('active_sitewide_plugins', []);
            
            // Usar is_network_admin() o combinar ambas condiciones para asegurarse
            // de que el código se ejecute tanto en la red como en el sitio principal.
            if (is_network_admin() || is_main_site()) {
                Logger::log("Plugins de red activos: " . implode(', ', array_keys($network_plugins)));
        
                $previous_network_plugins = get_site_option('plugin_monitor_last_network_plugins', []);
        
                // Detectar plugins que han sido desactivados en la red.
                $deactivated_network = array_diff_key($previous_network_plugins, $network_plugins);
                foreach ($deactivated_network as $plugin_path => $_) {
                    $message = "⚠️ El plugin de red '{$plugin_path}' fue desactivado en la red";
                    Logger::log($message);
                    self::send_email("Plugin de red desactivado", $message);
                }
        
                // Detectar plugins que han sido activados en la red.
                $activated_network = array_diff_key($network_plugins, $previous_network_plugins);
                foreach ($activated_network as $plugin_path => $_) {
                    $message = "✅ El plugin de red '{$plugin_path}' fue activado en la red";
                    Logger::log($message);
                    self::send_email("Plugin de red activado", $message);
                }
        
                update_site_option('plugin_monitor_last_network_plugins', $network_plugins);
            }
        }
        

        // 🔸 Plugins del sitio actual
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        $previous_plugins = get_option('plugin_monitor_last_plugins', []);

        foreach ($all_plugins as $path => $data) {
            $is_active = in_array($path, $active_plugins) || isset($network_plugins[$path]);
            $status = $is_active ? 'Activo' : 'Inactivo';
            Logger::log("Plugin: {$data['Name']} | Estado: $status");
        }

        $deactivated = array_diff($previous_plugins, $active_plugins);
        foreach ($deactivated as $plugin_path) {
            if (isset($network_plugins[$plugin_path])) continue;

            $data = $all_plugins[$plugin_path] ?? ['Name' => $plugin_path];
            $plugin_name = $data['Name'] ?? $plugin_path;
            $message = "⚠️ El plugin '{$plugin_name}' fue desactivado en el sitio '" . get_bloginfo('name') . "'";
            Logger::log($message);
            self::send_email("Plugin desactivado", $message);
        }

        $activated = array_diff($active_plugins, $previous_plugins);
        foreach ($activated as $plugin_path) {
            $data = $all_plugins[$plugin_path] ?? ['Name' => $plugin_path];
            $plugin_name = $data['Name'] ?? $plugin_path;
            $message = "✅ El plugin '{$plugin_name}' fue activado en el sitio '" . get_bloginfo('name') . "'";
            Logger::log($message);
            self::send_email("Plugin activado", $message);
        }

        update_option('plugin_monitor_last_plugins', $active_plugins);
    }

    public static function handle_php_errors($errno, $errstr, $errfile, $errline) {
        $types = [
            E_ERROR => 'ERROR', E_WARNING => 'WARNING', E_NOTICE => 'NOTICE',
            E_USER_ERROR => 'USER_ERROR', E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE', E_DEPRECATED => 'DEPRECATED'
        ];
        $type = $types[$errno] ?? "UNKNOWN";

        $message = "[{$type}] $errstr en $errfile línea $errline";
        Logger::log($message);
        self::send_email("Error PHP detectado", $message);

        return false;
    }

    public static function send_email($subject, $message) {
        wp_mail(
            self::$alert_email,
            $subject,
            $message,
            ['Content-Type: text/plain; charset=UTF-8']
        );
    }

    public static function on_activation() {
        update_option('plugin_monitor_last_plugins', get_option('active_plugins', []));
        if (is_multisite()) {
            update_site_option('plugin_monitor_last_network_plugins', get_site_option('active_sitewide_plugins', []));
        }
        Logger::log("Monitor WP Activado");
    }

    public static function on_deactivation() {
        delete_option('plugin_monitor_last_plugins');
        if (is_multisite()) {
            delete_site_option('plugin_monitor_last_network_plugins');
        }
        Logger::log("Monitor WP Desactivado");
    }
}
