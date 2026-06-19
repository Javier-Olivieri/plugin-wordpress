<?php
class AdminDashboard {
    public static function add_admin_menu() {
        add_menu_page(
            'Monitor WP',
            'Monitor WP',
            'manage_options',
            'monitor-wp',
            [self::class, 'render_dashboard'],
            'dashicons-dashboard',
            90
        );
    }

    public static function render_dashboard() {
        global $wpdb;
        $table = $wpdb->base_prefix . 'monitor_logs'; // uso correcto del prefijo

        $fecha_actual = current_time('Y-m-d');

        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE DATE(created_at) = %s ORDER BY created_at DESC LIMIT 50",
                $fecha_actual
            )
        );

        echo '<div class="wrap"><h1>Estado del Sistema</h1>';

        $status = ServerMonitor::get_status();
        echo '<h2>Estado del Servidor</h2><table class="widefat fixed striped">';
        foreach ($status as $label => $value) {
            echo "<tr><th>$label</th><td>$value</td></tr>";
        }
        echo '</table><br>';

        echo '<h2>Últimos eventos registrados</h2>';
        echo '<table class="widefat fixed striped"><thead><tr><th>Fecha</th><th>Mensaje</th></tr></thead><tbody>';

        foreach ($logs as $log) {
            $fecha = esc_html($log->created_at);
            $msg = esc_html($log->message);
            echo "<tr><td>$fecha</td><td>$msg</td></tr>";
        }

        echo '</tbody></table></div>';
    }
}
