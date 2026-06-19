<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

// Si es multisite, recorrer todos los blogs
if (is_multisite()) {
    $sites = get_sites();

    foreach ($sites as $site) {
        switch_to_blog($site->blog_id);

        $table = $wpdb->prefix . 'monitor_logs';
        $wpdb->query("DROP TABLE IF EXISTS $table");

        delete_option('plugin_monitor_last_plugins');

        restore_current_blog();
    }

    // Además eliminar el dato de alerta de red (site option)
    delete_site_option('monitor_ultima_alerta_hash');
} else {
    // Instalación individual
    $table = $wpdb->prefix . 'monitor_logs';
    $wpdb->query("DROP TABLE IF EXISTS $table");

    delete_option('plugin_monitor_last_plugins');
    delete_site_option('monitor_ultima_alerta_hash');
}
