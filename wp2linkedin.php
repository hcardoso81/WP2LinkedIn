<?php
/**
 * Plugin Name: WP LinkedIn Poster
 * Description: Conecta WordPress con LinkedIn (OAuth, selección de organización y publicación automática).
 * Version: 0.1
 * Author: Hernan Cardoso
 * Author URI: https://www.linkedin.com/in/cardosohernan/    
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

// Definir constantes (puedes mover a settings luego)
define( 'WPLP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPLP_URL', plugin_dir_url( __FILE__ ) );

// Autocarga de clases simples
require_once WPLP_PATH . 'includes/class-linkedin-admin.php';
require_once WPLP_PATH . 'includes/class-linkedin-oauth.php';
require_once WPLP_PATH . 'includes/class-linkedin-organizations.php';
require_once WPLP_PATH . 'includes/class-linkedin-poster.php';
require_once WPLP_PATH . 'includes/helpers.php';

// Inicializar el plugin
function wplp_init() {
    new WPLP_Admin();
    new WPLP_OAuth();
    new WPLP_Organizations();
    new WPLP_Poster();
}
add_action( 'plugins_loaded', 'wplp_init' );