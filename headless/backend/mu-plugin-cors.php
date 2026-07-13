<?php
/**
 * Plugin Name: Ora & Stone — Headless CORS Bridge
 * Description: Lets the separately-hosted headless frontend (headless/) call this
 *              site's WooCommerce Store API from another origin. Required because
 *              the frontend uses Cart-Token/Nonce headers instead of cookies, and
 *              a cross-origin fetch() can only read response headers the server
 *              explicitly exposes.
 *
 * INSTALL: copy this file into wp-content/mu-plugins/ on the WordPress site that
 * is acting as the backend (mu-plugins load automatically, no activation needed;
 * create the mu-plugins folder if it doesn't exist yet).
 *
 * CONFIGURE: edit $ora_stone_allowed_origins below to the exact origin(s) the
 * frontend is served from (protocol + host + port, no trailing slash, no path).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$ora_stone_allowed_origins = array(
	'https://your-frontend-domain.example',
	'http://localhost:8080', // local dev server for headless/
);

add_filter( 'allowed_http_origins', function ( $origins ) use ( $ora_stone_allowed_origins ) {
	return array_unique( array_merge( $origins, $ora_stone_allowed_origins ) );
} );

$ora_stone_send_cors_headers = function () use ( $ora_stone_allowed_origins ) {
	$origin = get_http_origin();
	if ( ! $origin || ! in_array( $origin, $ora_stone_allowed_origins, true ) ) {
		return;
	}
	header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
	header( 'Vary: Origin' );
	header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
	header( 'Access-Control-Allow-Headers: Content-Type, Nonce, Cart-Token' );
	// Store API write requests need the client to read these back off the
	// response — browsers hide all response headers cross-origin unless the
	// server lists them here.
	header( 'Access-Control-Expose-Headers: Nonce, Cart-Token, X-WP-Total, X-WP-TotalPages' );
};

// Normal REST requests.
add_filter( 'rest_pre_serve_request', function ( $served ) use ( $ora_stone_send_cors_headers ) {
	$ora_stone_send_cors_headers();
	return $served;
} );

// CORS preflight (OPTIONS) requests never reach rest_pre_serve_request.
add_action( 'init', function () use ( $ora_stone_send_cors_headers ) {
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
		$ora_stone_send_cors_headers();
		status_header( 200 );
		exit;
	}
} );
