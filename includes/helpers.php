<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hace una request genérica a la API de LinkedIn
 */
function wp2linkedin_api_request( string $method, string $url, string $token, array $body = [] ): array|false {
    if ( empty( $token ) ) {
        wp2linkedin_log( 'No access token provided' );
        return false;
    }

    $args = [
        'method'  => strtoupper( $method ),
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'X-Restli-Protocol-Version' => '2.0.0'
        ],
    ];

    if ( ! empty( $body ) ) {
        $args['body'] = wp_json_encode( $body );
    }

    $response = wp_remote_request( $url, $args );

    if ( is_wp_error( $response ) ) {
        wp2linkedin_log( 'Request error: ' . $response->get_error_message() );
        return false;
    }

    $code = wp_remote_retrieve_response_code( $response );
    $resp_body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        wp2linkedin_log( 'JSON decode error: ' . json_last_error_msg() );
        $resp_body = [];
    }

    wp2linkedin_log( sprintf( "Request %s %s - Code: %d - Body: %s", $method, $url, $code, print_r($resp_body, true) ) );

    return [
        'code' => $code,
        'body' => $resp_body
    ];
}

/**
 * Guarda logs en wp-content/debug.log
 */
function wp2linkedin_log( string $message ): void {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[WP2LinkedIn] ' . $message );
    }
}

/**
 * Devuelve el access token actual
 */
function wp2linkedin_get_token(): string {
    return get_option( 'wp2linkedin_access_token', '' );
}
