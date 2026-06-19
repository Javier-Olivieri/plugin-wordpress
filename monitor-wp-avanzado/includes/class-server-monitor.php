<?php
class ServerMonitor {
    public static $status = [];

    public static function init() {
        add_action('admin_init', [self::class, 'check_server_status']);
    }

    public static function check_server_status() {
        if (!current_user_can('manage_options')) return;

        $php = phpversion();
        $memory_limit = ini_get('memory_limit');
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [];
        $disk = disk_free_space('/') / (1024 * 1024 * 1024);
        $disk = round($disk, 2);
        $mem_usage = memory_get_usage(true) / (1024 * 1024);

        $alertas = [];

        if ($load && $load[0] > 2.5) {
            $alertas[] = "⚠️ Carga alta del servidor: {$load[0]}";
        }

        if ($disk < 1) {
            $alertas[] = "⚠️ Espacio en disco críticamente bajo: {$disk} GB";
        }

        if ($mem_usage > 200) {
            $alertas[] = "⚠️ Uso elevado de memoria: {$mem_usage} MB";
        }

        if (!empty($alertas)) {
            $mensaje = implode("\n", $alertas);
            Logger::log($mensaje);
            self::enviar_alerta_email($mensaje);
        } else {
            $carga = isset($load[0]) ? $load[0] : 'N/D';
            Logger::log("Servidor OK: PHP $php | Memoria {$memory_limit} | Carga {$carga} | Disco {$disk}GB");
        }

        self::$status = [
            'Versión de PHP' => $php,
            'Memoria límite' => $memory_limit,
            'Carga actual (1 min)' => isset($load[0]) ? $load[0] : 'N/D',
            'Uso de memoria actual' => round($mem_usage, 2) . ' MB',
            'Espacio libre en disco' => "{$disk} GB",
            'Alertas activas' => empty($alertas) ? 'Sin alertas ✅' : implode('<br>', $alertas)
        ];
    }

    private static function enviar_alerta_email($mensaje) {
        $hash_actual = md5($mensaje);
        $ultimo_hash = get_site_option('monitor_ultima_alerta_hash');

        if ($hash_actual === $ultimo_hash) return;

        $to = 'javier.olivieri@maxisistemas.com.ar';
        $subject = '🚨 Alerta del Servidor en WordPress';
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        $body = "Problemas detectados:\n\n$mensaje\n\nSitio: " . get_bloginfo('name') . "\nURL: " . home_url();

        wp_mail($to, $subject, $body, $headers);

        update_site_option('monitor_ultima_alerta_hash', $hash_actual);
    }

    public static function get_status() {
        return self::$status;
    }
}
