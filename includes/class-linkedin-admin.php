<?php
if (!defined('ABSPATH')) exit;

class WPLP_Admin
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_wplp_save_org', [$this, 'ajax_save_org']);
    }

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

    public function register_settings()
    {
        register_setting('wplp_settings', 'wp2linkedin_client_id');
        register_setting('wplp_settings', 'wp2linkedin_client_secret');
        register_setting('wplp_settings', 'wp2linkedin_redirect_uri');
    }

    public function enqueue_assets($hook)
    {
        if ($hook !== 'settings_page_wplp-settings') return;

        wp_enqueue_style('wplp-admin', WPLP_URL . 'assets/css/admin.css', [], '1.0');
        wp_enqueue_script('wplp-admin', WPLP_URL . 'assets/js/admin.js', ['jquery'], '1.0', true);

        wp_localize_script('wplp-admin', 'wplp', [
            'nonce' => wp_create_nonce('wplp_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
    }

    public function render_settings_page()
    {
        $client_id     = get_option('wp2linkedin_client_id');
        $client_secret = get_option('wp2linkedin_client_secret');
        $redirect_uri  = get_option('wp2linkedin_redirect_uri', admin_url('admin.php?page=linkedin-oauth'));
        $org_id        = get_option('wp2linkedin_default_org');

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
                            <input type="text" name="wp2linkedin_redirect_uri" value="<?php echo esc_attr($redirect_uri); ?>" class="regular-text" readonly>
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
                    <option value="<?php echo esc_attr($org_id); ?>" selected><?php echo esc_html($org_id); ?></option>
                <?php endif; ?>
            </select>
        </div>
<?php
    }

    public function ajax_save_org()
    {
        check_ajax_referer('wplp_nonce');

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
}
