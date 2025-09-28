<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hace una request genérica a la API de LinkedIn
 */
function wp2linkedin_api_request( $method, $url, $token, $body = [] ) {
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
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    wp2linkedin_log( "Request $method $url - Code: $code - Body: " . print_r( $body, true ) );

    return [
        'code' => $code,
        'body' => $body
    ];
}

/**
 * Guarda logs en wp-content/debug.log
 */
function wp2linkedin_log( $message ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[WP2LinkedIn] ' . $message );
    }
}

/**
 * Devuelve el access token actual
 */
function wp2linkedin_get_token() {
    return get_option( 'wp2linkedin_access_token' );
}
