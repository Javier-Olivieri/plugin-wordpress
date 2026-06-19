<?php
class Logger {
    public static function log($message) {
        global $wpdb;
        $table = $wpdb->base_prefix . 'monitor_logs'; // base_prefix para multisite

        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        $wpdb->insert($table, ['message' => $message]);
    }
}
