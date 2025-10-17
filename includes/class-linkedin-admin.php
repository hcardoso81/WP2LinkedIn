<?php
if (!defined('ABSPATH')) exit;

class WPLP_Admin
{
    public function __construct()
    {
        // Menú y ajustes
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_wplp_save_org', [$this, 'ajax_save_org']);

        add_action('wp_ajax_linkedin_publish_post', [$this, 'ajax_publish_post']); // AJAX del botón

        // Meta boxes
        add_action('add_meta_boxes', [$this, 'add_linkedin_metabox']);
    }

    // --- Menu ---
    public function add_menu_page()
    {
        add_options_page(
            'WP LinkedIn Poster',
            'WP LinkedIn Poster',
            'manage_options',
            'wplp-settings',
            [$this, 'render_settings_page']
        );
    }

    // --- Settings ---
    public function register_settings()
    {
        register_setting('wplp_settings', 'wp2linkedin_client_id');
        register_setting('wplp_settings', 'wp2linkedin_client_secret');
        register_setting('wplp_settings', 'wp2linkedin_redirect_uri');
    }

    // --- Enqueue CSS y JS ---
    public function enqueue_assets($hook)
    {
        // Solo en settings
        if ($hook === 'settings_page_wplp-settings' || get_current_screen()->post_type === 'post') {
            wp_enqueue_style('wplp-admin', WPLP_URL . 'assets/css/admin.css', [], '1.0');
            wp_enqueue_script('wplp-admin', WPLP_URL . 'assets/js/admin.js', ['jquery'], '1.0', true);

            wp_localize_script('wplp-admin', 'wplp', [
                'nonce'   => wp_create_nonce('linkedin_publish'),
                'ajaxurl' => admin_url('admin-ajax.php')
            ]);
        }
    }

    // --- Página de configuración ---
    public function render_settings_page()
    {
        $client_id     = get_option('wp2linkedin_client_id');
        $client_secret = get_option('wp2linkedin_client_secret');
        $redirect_uri  = get_option('wp2linkedin_redirect_uri', admin_url('admin.php?page=linkedin-oauth'));
        $org_id        = get_option('wp2linkedin_default_org');
        $org_name      = $org_id;

        if ($org_id) {
            $orgClass = new WPLP_Organizations();
            $orgs     = $orgClass->get_organizations();
            foreach ($orgs as $org) {
                if ($org['id'] === $org_id) {
                    $org_name = $org['name'];
                    break;
                }
            }
        }

        $oauth = new WPLP_OAuth();
?>
        <div class="wrap wp2linkedin-settings">
            <h2>WP LinkedIn Poster – Configuración</h2>

            <form method="post" action="options.php">
                <?php settings_fields('wplp_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wp2linkedin_client_id">Client ID</label></th>
                        <td><input type="text" name="wp2linkedin_client_id" value="<?php echo esc_attr($client_id); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wp2linkedin_client_secret">Client Secret</label></th>
                        <td><input type="password" name="wp2linkedin_client_secret" value="<?php echo esc_attr($client_secret); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Redirect URI</th>
                        <td>
                            <input type="text" name="wp2linkedin_redirect_uri" value="<?php echo esc_attr($redirect_uri); ?>" class="regular-text">
                            <p class="description">Copiar esta URL exactamente en la configuración de tu app de LinkedIn.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <h3>Autenticación con LinkedIn</h3>
            <?php if ($oauth->is_connected()): ?>
                <div class="wp2linkedin-status success">✅ Conectado a LinkedIn</div>
            <?php else: ?>
                <a class="button button-primary" href="<?php echo esc_url($oauth->get_auth_url()); ?>">Conectar con LinkedIn</a>
            <?php endif; ?>

            <h3>Organización por defecto</h3>
            <p>
                <button id="wp2linkedin-load-orgs" class="button">Cargar organizaciones</button>
            </p>
            <select id="wp2linkedin-org-select">
                <?php if ($org_id): ?>
                    <option value="<?php echo esc_attr($org_id); ?>" selected>
                        <?php echo esc_html($org_name); ?>
                    </option>
                <?php endif; ?>
            </select>
            <p>
                <button id="wp2linkedin-confirm-org" class="button button-primary">Confirmar organización</button>
            </p>

            <?php if ($org_id): ?>
                <div class="wp2linkedin-status success">
                    ✅ Organización por defecto: <strong><?php echo esc_html($org_id); ?></strong>
                </div>
            <?php endif; ?>
        </div>
    <?php
    }

    // --- Meta box ---
    public function add_linkedin_metabox()
    {
        add_meta_box(
            'linkedin_poster',
            'Publicar en LinkedIn',
            [$this, 'render_linkedin_metabox'],
            'post',
            'side',
            'high'
        );
    }

public function render_linkedin_metabox($post)
{
    $posted = get_post_meta($post->ID, '_linkedin_posted', true);
    $date   = get_post_meta($post->ID, '_linkedin_posted_date', true);

    $content_linkedin = function_exists('get_field') ? get_field('content_linkedin', $post->ID) : '';

    echo '<p>Estado en LinkedIn: ';
    if ($posted) {
        echo '<span style="color:green;">✅ Publicado</span>';
        if ($date) echo '<br><small>' . date('d/m/Y H:i', strtotime($date)) . '</small>';
    } else {
        echo '<span style="color:#ccc;">⏳ Pendiente</span>';
    }
    echo '</p>';

    // El botón ahora se desactiva si el contenido está vacío
    $disabled = empty(trim($content_linkedin)) ? 'disabled' : '';
    echo '<p><button type="button" class="button button-primary" id="linkedin-publish-btn" data-post-id="' . $post->ID . '" ' . $disabled . '>Publicar en LinkedIn</button></p>';

    wp_nonce_field('linkedin_publish', 'linkedin_publish_nonce');
}

    // --- AJAX publicar post ---
    public function ajax_publish_post()
    {
        check_ajax_referer('linkedin_publish', 'security');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'No tienes permisos']);
        }

        $post_id = intval($_POST['post_id']);
        if (!$post_id) wp_send_json_error(['message' => 'Post inválido']);

        $poster = new WPLP_Poster();
        $result = $poster->publish_to_linkedin($post_id, get_post($post_id));

        if ($result === true) {
            wp_send_json_success(['message' => '✅ Post publicado correctamente']);
        } else {
            wp_send_json_error(['message' => '❌ Error al publicar el post']);
        }
    }

    // --- AJAX guardar organización ---
    public function ajax_save_org()
    {
        check_ajax_referer('linkedin_publish');

        if (!current_user_can('manage_options')) wp_send_json_error();

        if (isset($_POST['org_id'])) {
            $org_id = sanitize_text_field($_POST['org_id']);
            update_option('wp2linkedin_default_org', $org_id);
            wp_send_json_success();
        }

        wp_send_json_error();
    }
}
