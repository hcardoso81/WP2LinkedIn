<?php
if (!defined('ABSPATH')) exit;

/**
 * Hace una request genérica a la API de LinkedIn
 *
 * @return array|false
 */
function wp2linkedin_api_request(
    string $method,
    string $url,
    string $token,
    array $body = []
) {

    if (empty($token)) {
        WPLP_Logger::error(
            'No access token provided',
            ['method' => $method, 'url' => $url]
        );
        return false;
    }

    $args = [
        'method'  => strtoupper($method),
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'X-Restli-Protocol-Version' => '2.0.0'
        ],
        'timeout' => 20,
    ];

    if (!empty($body)) {
        $args['body'] = wp_json_encode($body);
    }

    WPLP_Logger::info('LinkedIn API request started', [
        'method' => $method,
        'url'    => $url,
        'has_body' => !empty($body)
    ]);

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
        WPLP_Logger::error('LinkedIn API request failed', [
            'method' => $method,
            'url'    => $url,
            'error'  => $response->get_error_message(),
        ]);
        return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    $raw_body = wp_remote_retrieve_body($response);

    $resp_body = json_decode($raw_body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        WPLP_Logger::error('JSON decode error', [
            'method' => $method,
            'url'    => $url,
            'error'  => json_last_error_msg(),
            'raw'    => $raw_body,
        ]);
        $resp_body = [];
    }

    WPLP_Logger::info('LinkedIn API response received', [
        'method' => $method,
        'url'    => $url,
        'code'   => $code,
    ]);

    return [
        'code' => $code,
        'body' => $resp_body,
    ];
}

/**
 * Devuelve el access token actual
 */
function wp2linkedin_get_token(): string {
    return (string) get_option('wp2linkedin_access_token', '');
}

/**
 * Devuelve el contenido personalizado que se usara para LinkedIn.
 */
function wplp_get_linkedin_content($post_id): string {
    $content = get_post_meta((int) $post_id, 'content_linkedin', true);

    return is_string($content) ? $content : '';
}

/**
 * Limpia el contenido de LinkedIn para validar y enviar texto plano.
 */
function wplp_clean_linkedin_content(string $content): string {
    $content = wp_strip_all_tags($content);
    $content = html_entity_decode($content, ENT_QUOTES, get_bloginfo('charset'));
    $content = str_replace("\xC2\xA0", ' ', $content);

    return trim($content);
}

/**
 * Indica si el post ya tiene contenido real para publicar en LinkedIn.
 */
function wplp_has_linkedin_content($post_id): bool {
    return wplp_clean_linkedin_content(wplp_get_linkedin_content($post_id)) !== '';
}

/**
 * Estados posibles de publicacion en LinkedIn.
 */
function wplp_get_linkedin_statuses(): array {
    return [
        'pending' => 'Pendiente',
        'published' => 'Publicado',
        'manual_published' => 'Publicado manualmente',
        'scheduled' => 'Programado para publicar',
    ];
}

/**
 * Devuelve el estado normalizado de LinkedIn para un post.
 */
function wplp_get_linkedin_status($post_id): string {
    $status = get_post_meta((int) $post_id, '_linkedin_status', true);
    $statuses = wplp_get_linkedin_statuses();

    if (is_string($status) && isset($statuses[$status])) {
        return $status;
    }

    if (get_post_meta((int) $post_id, '_linkedin_posted', true)) {
        return 'published';
    }

    return 'pending';
}

/**
 * Indica si el estado actual bloquea una nueva publicacion manual.
 */
function wplp_is_linkedin_publish_locked($post_id): bool {
    $status = wplp_get_linkedin_status($post_id);

    return in_array($status, ['published', 'manual_published', 'scheduled'], true);
}

/**
 * Devuelve datos de presentacion para un estado de LinkedIn.
 */
function wplp_get_linkedin_status_display($status): array {
    $display = [
        'pending' => [
            'label' => 'Pendiente',
            'color' => '#8a6d3b',
            'icon' => 'dashicons-clock',
        ],
        'published' => [
            'label' => 'Publicado',
            'color' => '#00a32a',
            'icon' => 'dashicons-yes-alt',
        ],
        'manual_published' => [
            'label' => 'Publicado manualmente',
            'color' => '#00a32a',
            'icon' => 'dashicons-yes',
        ],
        'scheduled' => [
            'label' => 'Programado para publicar',
            'color' => '#2271b1',
            'icon' => 'dashicons-calendar-alt',
        ],
    ];

    return $display[$status] ?? $display['pending'];
}
