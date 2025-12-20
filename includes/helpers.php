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
