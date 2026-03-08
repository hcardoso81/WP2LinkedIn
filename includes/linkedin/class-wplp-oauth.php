<?php
if (!defined('ABSPATH')) exit;

class WPLP_OAuth {
    private $client_id;
    private $client_secret;
    private $redirect_uri;

    public function __construct() {
        // Cargar configuraciones desde la base de datos
        $this->load_settings();

        // Solo agregar hooks si las configuraciones están presentes
        if ($this->is_configured()) {
            add_action('admin_post_wp2linkedin_callback', [$this, 'handle_callback']);
            add_action('admin_post_nopriv_wp2linkedin_callback', [$this, 'handle_callback']);
            add_action('wp_ajax_test_linkedin_connection', [$this, 'ajax_test_connection']);
        }

        // AJAX para desconexión
        add_action('wp_ajax_wplp_disconnect', [$this, 'ajax_disconnect']);

        // Debug
        add_action('admin_notices', [$this, 'debug_notice']);
    }

    /**
     * Cargar configuraciones desde la base de datos
     */
    private function load_settings() {
        $this->client_id = get_option('wp2linkedin_client_id', '');
        $this->client_secret = get_option('wp2linkedin_client_secret', '');
        $this->redirect_uri = get_option(
            'wp2linkedin_redirect_uri',
            admin_url('admin-post.php?action=wp2linkedin_callback')
        );
    }

    /**
     * Verificar si la configuración está completa
     */
    public function is_configured() {
        return !empty($this->client_id) && !empty($this->client_secret);
    }

    /**
     * Generar URL de autorización de LinkedIn
     */
    public function get_auth_url() {
        if (!$this->is_configured()) return false;

        $state = wp_create_nonce('wp2linkedin_auth');

        return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'scope'         => 'r_basicprofile w_member_social w_organization_social rw_organization_admin',
            'state'         => $state,
        ]);
    }

    /**
     * Manejar callback de LinkedIn
     */
    public function handle_callback() {
        if (!isset($_GET['state']) || !wp_verify_nonce($_GET['state'], 'wp2linkedin_auth')) {
            wp_die('Invalid state. Posible ataque CSRF.');
        }

        if (isset($_GET['code'])) {
            $result = $this->exchange_code_for_token(sanitize_text_field($_GET['code']));
            if ($result) {
                wp_redirect(admin_url('options-general.php?page=wplp-settings&auth=success'));
            } else {
                wp_redirect(admin_url('options-general.php?page=wplp-settings&auth=error'));
            }
        } else {
            wp_redirect(admin_url('options-general.php?page=wplp-settings&auth=no_code'));
        }
        exit;
    }

    /**
     * Intercambiar código por token de LinkedIn
     */
    private function exchange_code_for_token($code) {
        $response = wp_remote_post('https://www.linkedin.com/oauth/v2/accessToken', [
            'body' => [
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $this->redirect_uri,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) return false;

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['access_token'])) {
            update_option('wp2linkedin_access_token', $data['access_token']);
            update_option('wp2linkedin_token_expires', time() + intval($data['expires_in']));
            return true;
        }

        return false;
    }

    /**
     * Verificar si hay token válido
     */
    public function is_connected() {
        $token = get_option('wp2linkedin_access_token');
        $expires = get_option('wp2linkedin_token_expires');
        return ($token && $expires && $expires > time());
    }

    /**
     * AJAX: Probar conexión a LinkedIn
     */
    public function ajax_test_connection() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos suficientes');
        }

        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'linkedin_test') ||
                           wp_verify_nonce($_POST['nonce'], 'wplp_nonce');
        }

        if (!$nonce_valid) {
            wp_send_json_error('Nonce inválido');
        }

        $access_token = get_option('wp2linkedin_access_token');
        if (!$access_token) {
            wp_send_json_error('No hay token de acceso');
        }

        $response = wp_remote_get('https://api.linkedin.com/v2/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Error de conexión: ' . $response->get_error_message());
        }

        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code === 200) {
            wp_send_json_success('Conexión exitosa');
        } else {
            wp_send_json_error('Error HTTP ' . $http_code);
        }
    }

    /**
     * AJAX: Desconectar
     */
    public function ajax_disconnect() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos suficientes');
        }

        check_ajax_referer('wplp_nonce');

        delete_option('wp2linkedin_access_token');
        delete_option('wp2linkedin_token_expires');
        delete_option('wp2linkedin_default_org');

        wp_send_json_success('Desconectado exitosamente');
    }

    /**
     * Mostrar debug en admin
     */
    public function debug_notice() {
        if (current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'wplp-settings') {
            if (isset($_GET['debug'])) {
                echo '<div class="notice notice-info">';
                echo '<h4>Debug WP2LinkedIn OAuth:</h4>';
                echo '<ul>';
                echo '<li><strong>Client ID:</strong> ' . ($this->client_id ? 'Configurado' : 'NO configurado') . '</li>';
                echo '<li><strong>Client Secret:</strong> ' . ($this->client_secret ? 'Configurado' : 'NO configurado') . '</li>';
                echo '<li><strong>Redirect URI:</strong> ' . esc_html($this->redirect_uri) . '</li>';
                echo '<li><strong>Conectado:</strong> ' . ($this->is_connected() ? 'SÍ' : 'NO') . '</li>';
                echo '<li><strong>Token:</strong> ' . (get_option('wp2linkedin_access_token') ? 'Existe' : 'No existe') . '</li>';
                echo '</ul>';
                echo '</div>';
            }
        }
    }

    /**
     * Obtener configuraciones actuales
     */
    public function get_settings() {
        return [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $this->redirect_uri,
            'is_configured' => $this->is_configured(),
            'is_connected' => $this->is_connected()
        ];
    }
}
