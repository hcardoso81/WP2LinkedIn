<?php
if (!defined('ABSPATH')) exit;

class WPLP_OAuth {
    private $client_id;
    private $client_secret;
    private $redirect_uri;

    public function __construct() {
        // Tomar valores desde la base de datos (admin configurable)
        $this->client_id     = get_option('wp2linkedin_client_id', '');
        $this->client_secret = get_option('wp2linkedin_client_secret', '');
        $this->redirect_uri  = get_option('wp2linkedin_redirect_uri', admin_url('admin.php?page=linkedin-oauth'));

        // Callback de LinkedIn
        add_action('admin_post_wp2linkedin_callback', [$this, 'handle_callback']);
    }

    // Generar URL de autorización
    public function get_auth_url() {
        $state = wp_generate_password(32, false);
        set_transient('wp2linkedin_oauth_state', $state, 600); // 10 minutos

        return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'scope'         => 'r_liteprofile r_emailaddress w_member_social w_organization_social rw_organization_admin',
            'state'         => $state,
        ]);
    }

    // Manejar callback de LinkedIn
    public function handle_callback() {
        $state_received = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
        $stored_state   = get_transient('wp2linkedin_oauth_state');

        if (!$state_received || $state_received !== $stored_state) {
            wp_die('Estado inválido o expirado. Posible ataque CSRF.');
        }

        delete_transient('wp2linkedin_oauth_state');

        if (isset($_GET['code'])) {
            $this->exchange_code_for_token(sanitize_text_field($_GET['code']));
        }

        wp_redirect(admin_url('admin.php?page=linkedin-oauth&auth=success'));
        exit;
    }

    // Intercambiar código por token
    private function exchange_code_for_token($code) {
        if (empty($this->client_id) || empty($this->client_secret)) return;

        $response = wp_remote_post('https://www.linkedin.com/oauth/v2/accessToken', [
            'body' => [
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $this->redirect_uri,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
            ]
        ]);

        if (is_wp_error($response)) return;

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['access_token'])) {
            update_option('wp2linkedin_access_token', $data['access_token']);
            update_option('wp2linkedin_token_expires', time() + intval($data['expires_in']));
        }
    }

    // Verificar conexión activa
    public function is_connected() {
        $token   = get_option('wp2linkedin_access_token');
        $expires = get_option('wp2linkedin_token_expires');
        return ($token && $expires && $expires > time());
    }
}
