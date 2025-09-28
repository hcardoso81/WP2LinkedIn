<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPLP_Organizations {
    private $token;

    public function __construct() {
        $this->token = get_option( 'wp2linkedin_access_token', '' );

        add_action( 'wp_ajax_wp2linkedin_get_orgs', [ $this, 'ajax_get_organizations' ] );
    }

    public function get_organizations() {
        if ( empty( $this->token ) ) {
            return [];
        }

        $response = wp_remote_get( "https://api.linkedin.com/v2/organizationalEntityAcls?q=roleAssignee&role=ADMINISTRATOR", [
            'headers' => [ 'Authorization' => 'Bearer ' . $this->token ]
        ] );

        if ( is_wp_error( $response ) ) return [];

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return [];
        }

        $orgs = [];
        if ( isset( $data['elements'] ) ) {
            foreach ( $data['elements'] as $el ) {
                if ( isset( $el['organizationalTarget'] ) ) {
                    $orgs[] = str_replace( 'urn:li:organization:', '', $el['organizationalTarget'] );
                }
            }
        }
        return $orgs;
    }

    public function ajax_get_organizations() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die();

        $orgs = $this->get_organizations();
        wp_send_json_success( $orgs );
    }

    public function set_default_organization( $org_id ) {
        update_option( 'wp2linkedin_default_org', sanitize_text_field( $org_id ) );
    }

    public function get_default_organization() {
        return get_option( 'wp2linkedin_default_org', '' );
    }
}
