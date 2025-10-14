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

    // Mostrar columna LinkedIn en listado de posts
    add_filter('manage_posts_columns', function($columns) {
        $columns['linkedin_status'] = 'LinkedIn';
        return $columns;
    });

    add_action('manage_posts_custom_column', function($column, $post_id) {
        if ($column === 'linkedin_status') {
            $posted = get_post_meta($post_id, '_linkedin_posted', true);
            if ($posted) {
                $date = get_post_meta($post_id, '_linkedin_posted_date', true);
                echo '<span style="color:green;">✅ Publicado</span>';
                if ($date) {
                    echo '<br><small>' . date('d/m/Y H:i', strtotime($date)) . '</small>';
                }
            } else {
                echo '<span style="color:#ccc;">⏳ Pendiente</span>';
            }
        }
    }, 10, 2);
}

add_action('plugins_loaded', 'wplp_init');

// Registrar redirect URI predeterminada al activar el plugin
function wplp_activate() {
    if (!get_option('wp2linkedin_redirect_uri')) {
        update_option('wp2linkedin_redirect_uri', admin_url('admin-post.php?action=wp2linkedin_callback'));
    }
}
register_activation_hook(__FILE__, 'wplp_activate');

// Crear campo ACF "content_linkedin" al cargar ACF
function wplp_register_acf_field() {
    if ( function_exists('acf_add_local_field_group') ) {

        acf_add_local_field_group([
            'key' => 'group_wplp_linkedin',
            'title' => 'Contenido para LinkedIn',
            'fields' => [
                [
                    'key' => 'field_wplp_content_linkedin',
                    'label' => 'Contenido para LinkedIn',
                    'name' => 'content_linkedin',
                    'type' => 'wysiwyg',
                    'instructions' => 'Este contenido se usará para publicar en LinkedIn si está completo.',
                    'required' => 0,
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 0,
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ],
                ],
            ],
            'position' => 'acf_after_title',
            'style' => 'default',
            'label_placement' => 'top',
            'active' => true,
        ]);
    }
}
add_action('acf/init', 'wplp_register_acf_field');