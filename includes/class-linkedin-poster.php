<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPLP_Poster {
    private $token;
    private $org_id;

    public function __construct() {
        $this->token  = get_option( 'wp2linkedin_access_token', '' );
        $this->org_id = get_option( 'wp2linkedin_default_org', '' );

        add_action( 'publish_post', [ $this, 'publish_to_linkedin' ], 10, 2 );
    }

    public function publish_to_linkedin( $post_id, $post ) {
        // Evitar borradores, revisiones, repeticiones
        if ( wp_is_post_revision( $post_id ) ) return;
        if ( get_post_meta( $post_id, '_linkedin_posted', true ) ) return;

        // Validar token y organización
        if ( empty( $this->token ) || empty( $this->org_id ) ) return;

        $title   = get_the_title( $post_id );
        $excerpt = wp_trim_words( strip_tags( $post->post_content ), 40 );
        $url     = get_permalink( $post_id );
        $image   = get_the_post_thumbnail_url( $post_id, 'full' );

        $body = [
            'author' => "urn:li:organization:" . $this->org_id,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [ 'text' => $title . "\n\n" . $excerpt ],
                    'shareMediaCategory' => $image ? 'IMAGE' : 'ARTICLE',
                    'media' => $image ? [[
                        'status' => 'READY',
                        'description' => [ 'text' => $excerpt ],
                        'originalUrl' => $image,
                        'title' => [ 'text' => $title ],
                        'thumbnails' => [[ 'resolvedUrl' => $image ]]
                    ]] : []
                ]
            ],
            'visibility' => [ 'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC' ]
        ];

        $response = wp_remote_post( "https://api.linkedin.com/v2/ugcPosts", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type'  => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0'
            ],
            'body' => wp_json_encode( $body )
        ] );

        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 201 ) {
            update_post_meta( $post_id, '_linkedin_posted', 1 );
        } else {
            // Logging para depuración
            error_log( 'LinkedIn posting error: ' . print_r( $response, true ) );
        }
    }
}
