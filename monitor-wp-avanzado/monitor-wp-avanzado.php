<?php
/**
 * Plugin Name: Monitor WP Avanzado
 * Description: Monitorea el comportamiento de los plugins y el estado del servidor. Envía alertas y muestra paneles con el estado del sistema.
 * Version: 1.0
 * Author: Tu Nombre
 * Text Domain: monitor-wp-avanzado
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-plugin-monitor.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-server-monitor.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-logger.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-dashboard.php';

register_activation_hook(__FILE__, ['PluginMonitor', 'on_activation']);
register_deactivation_hook(__FILE__, ['PluginMonitor', 'on_deactivation']);

add_action('init', ['PluginMonitor', 'init']);
add_action('init', ['ServerMonitor', 'init']);
add_action('admin_menu', ['AdminDashboard', 'add_admin_menu']);
