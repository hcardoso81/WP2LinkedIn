<?php
/**
 * Plugin Name: WP LinkedIn Poster
 * Description: Publica posts en LinkedIn desde WordPress con OAuth, organizacion seleccionada, contenido ACF y estados editables de publicacion.
 * Version: 3.1
 * Author: Hernan Cardoso
 * Author URI: https://www.linkedin.com/in/cardosohernan/
 */

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| Constantes del plugin
|--------------------------------------------------------------------------
*/

define('WPLP_PATH', plugin_dir_path(__FILE__));
define('WPLP_URL', plugin_dir_url(__FILE__));

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

require_once WPLP_PATH . 'includes/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Inicializar plugin
|--------------------------------------------------------------------------
*/

function wplp_init() {

    $plugin = new WPLP_Plugin();
    $plugin->init();

}

add_action('plugins_loaded', 'wplp_init');


/*
|--------------------------------------------------------------------------
| Activación del plugin
|--------------------------------------------------------------------------
*/

function wplp_activate() {

    if (!get_option('wp2linkedin_redirect_uri')) {
        update_option(
            'wp2linkedin_redirect_uri',
            admin_url('admin-post.php?action=wp2linkedin_callback')
        );
    }

}

register_activation_hook(__FILE__, 'wplp_activate');
