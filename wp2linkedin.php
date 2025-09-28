<?php
/**
 * Plugin Name: WP LinkedIn Poster
 * Description: Conecta WordPress con LinkedIn (OAuth, selección de organización y publicación automática).
 * Version: 0.1
 * Author: Hernan Cardoso
 * Author URI: https://www.linkedin.com/in/cardosohernan/
 */

if (!defined('ABSPATH')) exit;

// Definir constantes
define('WPLP_PATH', plugin_dir_path(__FILE__));
define('WPLP_URL', plugin_dir_url(__FILE__));

// Incluir archivos
require_once WPLP_PATH . 'includes/class-linkedin-admin.php';
require_once WPLP_PATH . 'includes/class-linkedin-oauth.php';
require_once WPLP_PATH . 'includes/class-linkedin-organizations.php';
require_once WPLP_PATH . 'includes/class-linkedin-poster.php';
require_once WPLP_PATH . 'includes/helpers.php';

// Inicializar plugin
function wplp_init() {
    new WPLP_Admin();
    new WPLP_OAuth();
    new WPLP_Organizations();
    new WPLP_Poster();
}
add_action('plugins_loaded', 'wplp_init');

// Registrar redirect URI predeterminada al activar el plugin
function wplp_activate() {
    if (!get_option('wp2linkedin_redirect_uri')) {
        update_option('wp2linkedin_redirect_uri', admin_url('admin-post.php?action=wp2linkedin_callback'));
    }
}
register_activation_hook(__FILE__, 'wplp_activate');
