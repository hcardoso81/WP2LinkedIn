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
        add_action('wp_ajax_linkedin_publish_post', [$this, 'ajax_publish_post']);

        // Meta boxes
        add_action('add_meta_boxes', [$this, 'add_linkedin_metabox']);

        // Aviso de token expirado
        add_action('admin_notices', [$this, 'token_expired_notice']);
    }

    // --- Menu ---
    public function add_menu_page()
    {
        add_menu_page(
            'WP LinkedIn Poster',
            'LinkedIn Poster',
            'manage_options',
            'wplp-dashboard',
            [$this, 'render_settings_page'],
            'dashicons-linkedin',
            26
        );

        add_submenu_page(
            'wplp-dashboard',
            'Reconectar LinkedIn',
            'Reconectar LinkedIn',
            'manage_options',
            'wplp-reconnect',
            [$this, 'render_reconnect_page']
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
        $screen = get_current_screen();

        if ($hook === 'toplevel_page_wplp-dashboard' || ($screen && $screen->post_type === 'post')) {

            wp_enqueue_style(
                'wplp-admin',
                WPLP_URL . 'assets/css/admin.css',
                [],
                '1.1'
            );

            wp_enqueue_script(
                'wplp-admin',
                WPLP_URL . 'assets/js/admin.js',
                ['jquery'],
                '1.1',
                true
            );

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

        $redirect_uri  = get_option(
            'wp2linkedin_redirect_uri',
            admin_url('admin-post.php?action=wp2linkedin_callback')
        );

        $org_id   = get_option('wp2linkedin_default_org');
        $org_name = $org_id;

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
                        <th scope="row">Client ID</th>
                        <td>
                            <input type="text" name="wp2linkedin_client_id"
                                value="<?php echo esc_attr($client_id); ?>"
                                class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Client Secret</th>
                        <td>
                            <input type="password" name="wp2linkedin_client_secret"
                                value="<?php echo esc_attr($client_secret); ?>"
                                class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Redirect URI</th>
                        <td>
                            <input type="text"
                                name="wp2linkedin_redirect_uri"
                                value="<?php echo esc_attr($redirect_uri); ?>"
                                class="regular-text">

                            <p class="description">
                                Copiar esta URL exactamente en la app de LinkedIn.
                            </p>
                        </td>
                    </tr>

                </table>

                <?php submit_button(); ?>
            </form>

            <h3>Autenticación con LinkedIn</h3>

            <?php if ($oauth->is_connected()): ?>

                <div class="wp2linkedin-status success">
                    ✅ Conectado a LinkedIn
                </div>

            <?php else: ?>

                <a class="button button-primary"
                    href="<?php echo esc_url($oauth->get_auth_url()); ?>">
                    Conectar con LinkedIn
                </a>

            <?php endif; ?>

            <h3>Organización por defecto</h3>

            <p>
                <button id="wp2linkedin-load-orgs" class="button">
                    Cargar organizaciones
                </button>
            </p>

            <select id="wp2linkedin-org-select">
                <?php if ($org_id): ?>
                    <option value="<?php echo esc_attr($org_id); ?>" selected>
                        <?php echo esc_html($org_name); ?>
                    </option>
                <?php endif; ?>
            </select>

            <p>
                <button id="wp2linkedin-confirm-org"
                    class="button button-primary">
                    Confirmar organización
                </button>
            </p>

        </div>
    <?php
    }

    // --- Página reconectar ---
    public function render_reconnect_page()
    {
        if (isset($_POST['wplp_reset_token'])) {

            delete_option('wp2linkedin_access_token');

            echo '<div class="notice notice-success"><p>✅ Token eliminado correctamente.</p></div>';
        }

        $oauth = new WPLP_OAuth();
    ?>

        <div class="wrap">

            <h1>Reconectar LinkedIn</h1>

            <p>
                Si el token expiró puedes eliminarlo y volver a conectar tu cuenta de LinkedIn.
            </p>

            <form method="post">
                <?php submit_button('Eliminar token actual', 'delete', 'wplp_reset_token'); ?>
            </form>

            <hr>

            <p>
                Después de eliminar el token puedes generar uno nuevo:
            </p>

            <a class="button button-primary"
                href="<?php echo esc_url($oauth->get_auth_url()); ?>">
                Reconectar con LinkedIn
            </a>

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
    $has_content = wplp_has_linkedin_content($post->ID);

    echo '<p>Estado en LinkedIn: ';

    if ($posted) {
        echo '<span style="color:green;">✅ Publicado</span>';
        if ($date) {
            echo '<br><small>' . date('d/m/Y H:i', strtotime($date)) . '</small>';
        }
    } else {
        echo '<span style="color:#ccc;">⏳ Pendiente</span>';
    }

    echo '</p>';

    echo '<p>Contenido LinkedIn: ';

    if ($has_content) {
        echo '<span class="wplp-column-icon wplp-column-icon--ok" title="Con contenido para LinkedIn"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span class="screen-reader-text">Con contenido para LinkedIn</span></span>';
    } else {
        echo '<span class="wplp-column-icon wplp-column-icon--empty" title="Contenido LinkedIn vacio"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span><span class="screen-reader-text">Contenido LinkedIn vacio</span></span>';
    }

    echo '</p>';

    if (!$posted && !$has_content) {
        echo '<p class="description">Completa el campo Contenido para LinkedIn y guarda el post antes de publicar.</p>';
    }

    $disabled = ($posted || !$has_content) ? 'disabled' : '';

    echo '<p>
            <button type="button"
                class="button button-primary"
                id="linkedin-publish-btn"
                data-post-id="' . $post->ID . '"
                ' . $disabled . '>
                Publicar en LinkedIn
            </button>
          </p>';

    // ✅ Contenedor para mensajes HTML (token expirado, errores, etc.)
    echo '<div id="linkedin-status" style="margin-top:10px;"></div>';

    wp_nonce_field('linkedin_publish', 'linkedin_publish_nonce');
}
    // --- AJAX publicar ---
   public function ajax_publish_post()
{
    check_ajax_referer('linkedin_publish', 'security');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
    }

    $post_id = intval($_POST['post_id']);

    if (!$post_id) {
        wp_send_json_error(['message' => 'Post inválido']);
    }

    $already_posted = get_post_meta($post_id, '_linkedin_posted', true);

    if ($already_posted) {
        wp_send_json_error(['message' => 'Este post ya fue publicado']);
    }

    $poster = new WPLP_Poster();

    $post = get_post($post_id);

    $result = $poster->publish_to_linkedin($post_id, $post);

    if ($result === true) {
        wp_send_json_success([
            'message' => 'Publicado correctamente en LinkedIn'
        ]);
    }

    // --- NUEVO: Manejo de token expirado ---
    if (is_array($result) && isset($result['error']) && $result['error'] === 'token_expired') {
        $reconnect_url = admin_url('admin.php?page=wplp-reconnect');
        wp_send_json_error([
            'message' => '⚠ Tu token de LinkedIn expiró. <a href="' . esc_url($reconnect_url) . '">Reconectar ahora</a>'
        ]);
    }
    // -------------------------------------------

    if (is_array($result) && isset($result['message'])) {
        $message = $result['message'];

        // Traducir errores de LinkedIn a mensajes amigables
        if (strpos($message, 'organizationUgcAuthorizations') !== false) {
            $message = '❌ Tu usuario no tiene permisos para publicar en esta página de LinkedIn. Verifica que seas administrador o Content Admin de la página.';
        }

        if (strpos($message, 'AUTH_DELEGATION_DENIED') !== false) {
            $message = '❌ LinkedIn rechazó la publicación por falta de permisos.';
        }

        wp_send_json_error([
            'message' => $message,
            'http_code' => $result['http_code'] ?? null
        ]);
    }

    wp_send_json_error([
        'message' => 'Error desconocido al publicar'
    ]);
}

    // --- AJAX guardar organización ---
    public function ajax_save_org()
    {
        check_ajax_referer('linkedin_publish', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }

        if (isset($_POST['org_id'])) {

            $org_id = sanitize_text_field($_POST['org_id']);

            update_option('wp2linkedin_default_org', $org_id);

            wp_send_json_success();
        }

        wp_send_json_error();
    }

    // --- Aviso token expirado ---
    public function token_expired_notice()
    {
        if (!current_user_can('manage_options')) return;

        $expired = get_option('wplp_token_expired');

        if (!$expired) return;

        $url = admin_url('admin.php?page=wplp-reconnect');

        echo '<div class="notice notice-error">';
        echo '<p>⚠ <strong>LinkedIn Poster:</strong> Tu token expiró. ';
        echo '<a href="' . esc_url($url) . '">Reconectar ahora</a></p>';
        echo '</div>';
    }
}
