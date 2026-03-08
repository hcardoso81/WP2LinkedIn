<?php
if (!defined('ABSPATH')) exit;

class WPLP_Organizations {
    private $token;

    public function __construct() {
        $this->token = get_option('wp2linkedin_access_token');
        add_action('wp_ajax_wp2linkedin_get_orgs', [$this, 'ajax_get_organizations']);
    }

public function get_organizations() {
    $response = wp_remote_get('https://api.linkedin.com/v2/organizationalEntityAcls?q=roleAssignee&role=ADMINISTRATOR', [
        'headers' => ['Authorization' => 'Bearer ' . $this->token]
    ]);

    if (is_wp_error($response)) return [];

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $orgs = [];

    if (isset($data['elements'])) {
        foreach ($data['elements'] as $el) {
            if (isset($el['organizationalTarget'])) {
                $orgId = str_replace('urn:li:organization:', '', $el['organizationalTarget']);

                // Obtener el nombre de la organización
                $nameResponse = wp_remote_get("https://api.linkedin.com/v2/organizations/$orgId", [
                    'headers' => ['Authorization' => 'Bearer ' . $this->token]
                ]);

                $nameData = json_decode(wp_remote_retrieve_body($nameResponse), true);
                $orgName = $nameData['localizedName'] ?? $orgId;

                $orgs[] = [
                    'id' => $orgId,
                    'name' => $orgName
                ];
            }
        }
    }

    return $orgs;
}

    public function ajax_get_organizations() {
        if (!current_user_can('manage_options')) wp_die();
        $orgs = $this->get_organizations();
        wp_send_json($orgs);
    }

    public function set_default_organization($org_id) {
        update_option('wp2linkedin_default_org', sanitize_text_field($org_id));
    }

    public function get_default_organization() {
        return get_option('wp2linkedin_default_org');
    }
}
