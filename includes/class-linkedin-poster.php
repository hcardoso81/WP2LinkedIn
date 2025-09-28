<?php
if (!defined('ABSPATH')) exit;

class WPLP_Poster {
    private $token;
    private $org_id;

    public function __construct() {
        $this->token  = get_option('wp2linkedin_access_token');
        $this->org_id = get_option('wp2linkedin_default_org');

        add_action('publish_post', [$this, 'publish_to_linkedin'], 10, 2);
    }

    public function publish_to_linkedin($post_id, $post) {
        if (wp_is_post_revision($post_id)) return;

        // Evitar duplicados
        if (get_post_meta($post_id, '_linkedin_posted', true)) {
            error_log("LinkedIn Poster: Post $post_id ya publicado.");
            return;
        }

        // Validaciones
        if (!$this->token) {
            error_log("LinkedIn Poster ERROR: No hay token configurado.");
            return;
        }

        if (!$this->org_id) {
            error_log("LinkedIn Poster ERROR: No hay organización por defecto configurada.");
            return;
        }

        $title   = get_the_title($post_id);
        $excerpt = wp_trim_words(strip_tags($post->post_content), 40);
        $url     = get_permalink($post_id);
        $image   = get_the_post_thumbnail_url($post_id, 'full');

        $body = [
            'author' => "urn:li:organization:" . $this->org_id,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => ['text' => $title . "\n\n" . $excerpt],
                    'shareMediaCategory' => $image ? 'IMAGE' : 'ARTICLE',
                    'media' => $image ? [[
                        'status' => 'READY',
                        'description' => ['text' => $excerpt],
                        'originalUrl' => $url,
                        'title' => ['text' => $title],
                        'thumbnails' => [['resolvedUrl' => $image]],
                    ]] : []
                ]
            ],
            'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC']
        ];

        $response = wp_remote_post('https://api.linkedin.com/v2/ugcPosts', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type'  => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0'
            ],
            'body' => wp_json_encode($body)
        ]);

        // Logs detallados para depuración
        if (is_wp_error($response)) {
            error_log("LinkedIn Poster ERROR (WP_Error): " . $response->get_error_message());
            return;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body_resp = wp_remote_retrieve_body($response);

        error_log("LinkedIn Poster: HTTP $http_code - Response: $body_resp");

        if ($http_code === 201) {
            update_post_meta($post_id, '_linkedin_posted', 1);
            update_post_meta($post_id, '_linkedin_posted_date', current_time('mysql'));
            error_log("LinkedIn Poster: Post $post_id publicado correctamente.");
        } else {
            error_log("LinkedIn Poster ERROR: No se pudo publicar el post $post_id.");
        }
    }
}
