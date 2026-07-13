<?php
/**
 * Plugin Name: Ora & Stone — Headless Account API
 * Description: Custom REST routes the headless frontend needs for registration,
 *              order history, and profile/address management — none of which
 *              WooCommerce's Store API or a JWT plugin expose on their own.
 *
 * REQUIRES: "JWT Authentication for WP REST API" plugin (install from Plugins →
 * Add New in wp-admin, search that exact name), plus in wp-config.php:
 *
 *     define( 'JWT_AUTH_SECRET_KEY', 'put-a-long-random-string-here' );
 *     define( 'JWT_AUTH_CORS_ENABLE', true );
 *
 * That plugin provides POST /wp-json/jwt-auth/v1/token (login) and makes
 * wp_get_current_user() resolve correctly for every REST request that carries
 * an "Authorization: Bearer <token>" header — including the routes below and
 * WooCommerce's own Store API, which is what attaches an order placed at
 * checkout to the logged-in customer's account.
 *
 * INSTALL: copy this file into wp-content/mu-plugins/ alongside
 * mu-plugin-cors.php (same folder, both load automatically).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

	register_rest_route( 'oraandstone/v1', '/register', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $request ) {
			$email      = sanitize_email( $request->get_param( 'email' ) );
			$password   = (string) $request->get_param( 'password' );
			$first_name = sanitize_text_field( $request->get_param( 'first_name' ) );
			$last_name  = sanitize_text_field( $request->get_param( 'last_name' ) );

			if ( ! $email || ! is_email( $email ) ) {
				return new WP_Error( 'invalid_email', 'A valid email address is required.', array( 'status' => 400 ) );
			}
			if ( strlen( $password ) < 8 ) {
				return new WP_Error( 'weak_password', 'Password must be at least 8 characters.', array( 'status' => 400 ) );
			}
			if ( email_exists( $email ) ) {
				return new WP_Error( 'email_exists', 'An account with this email already exists.', array( 'status' => 409 ) );
			}

			$user_id = wc_create_new_customer( $email, '', $password, array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
			) );

			if ( is_wp_error( $user_id ) ) {
				return new WP_Error( 'registration_failed', $user_id->get_error_message(), array( 'status' => 400 ) );
			}

			return array( 'success' => true, 'user_id' => $user_id );
		},
	) );

	register_rest_route( 'oraandstone/v1', '/orders', array(
		'methods'             => 'GET',
		'permission_callback' => function () { return is_user_logged_in(); },
		'callback'            => function () {
			$orders = wc_get_orders( array(
				'customer_id' => get_current_user_id(),
				'limit'       => -1,
				'orderby'     => 'date',
				'order'       => 'DESC',
			) );
			return array_map( 'oraandstone_format_order_summary', $orders );
		},
	) );

	register_rest_route( 'oraandstone/v1', '/orders/(?P<id>\d+)', array(
		'methods'             => 'GET',
		'permission_callback' => function () { return is_user_logged_in(); },
		'callback'            => function ( WP_REST_Request $request ) {
			$order = wc_get_order( (int) $request->get_param( 'id' ) );
			if ( ! $order || $order->get_customer_id() !== get_current_user_id() ) {
				return new WP_Error( 'not_found', 'Order not found.', array( 'status' => 404 ) );
			}
			return oraandstone_format_order_detail( $order );
		},
	) );

	register_rest_route( 'oraandstone/v1', '/account', array(
		'methods'             => 'GET',
		'permission_callback' => function () { return is_user_logged_in(); },
		'callback'            => function () {
			$customer = new WC_Customer( get_current_user_id() );
			return oraandstone_format_customer( $customer );
		},
	) );

	register_rest_route( 'oraandstone/v1', '/account', array(
		'methods'             => 'POST',
		'permission_callback' => function () { return is_user_logged_in(); },
		'callback'            => function ( WP_REST_Request $request ) {
			$customer = new WC_Customer( get_current_user_id() );

			if ( $request->get_param( 'first_name' ) !== null ) $customer->set_first_name( sanitize_text_field( $request->get_param( 'first_name' ) ) );
			if ( $request->get_param( 'last_name' ) !== null ) $customer->set_last_name( sanitize_text_field( $request->get_param( 'last_name' ) ) );

			foreach ( array( 'billing', 'shipping' ) as $group ) {
				$data = $request->get_param( $group );
				if ( ! is_array( $data ) ) continue;
				foreach ( array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone', 'email' ) as $field ) {
					if ( ! isset( $data[ $field ] ) ) continue;
					if ( 'email' === $field && 'shipping' === $group ) continue; // shipping has no email field
					$setter = "set_{$group}_{$field}";
					if ( method_exists( $customer, $setter ) ) {
						$customer->$setter( sanitize_text_field( $data[ $field ] ) );
					}
				}
			}

			$customer->save();
			return oraandstone_format_customer( $customer );
		},
	) );
} );

function oraandstone_format_order_summary( WC_Order $order ) {
	return array(
		'id'           => $order->get_id(),
		'number'       => $order->get_order_number(),
		'status'       => $order->get_status(),
		'date_created' => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : null,
		'total'        => $order->get_total(),
		'currency'     => $order->get_currency(),
		'item_count'   => $order->get_item_count(),
	);
}

function oraandstone_format_order_detail( WC_Order $order ) {
	$items = array();
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		$items[] = array(
			'name'     => $item->get_name(),
			'quantity' => $item->get_quantity(),
			'total'    => $item->get_total(),
			'image'    => $product ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '',
			'product_id' => $product ? $product->get_id() : 0,
		);
	}

	// Mirrors inc/orders.php's oraandstone_order_timeline() from the PHP
	// theme exactly, so the two builds show the same fulfillment steps for
	// the same order — WooCommerce itself has no literal timeline, just a
	// status, so this is the theme's own mapping onto Placed/Processing/
	// Shipped/Delivered, reused here rather than reinvented.
	$status           = $order->get_status();
	$is_stopped       = in_array( $status, array( 'cancelled', 'failed', 'refunded' ), true );
	$stopped_messages = array(
		'cancelled' => 'This order was cancelled.',
		'failed'    => 'Payment failed for this order.',
		'refunded'  => 'This order was refunded.',
	);

	$tracking_number  = $order->get_meta( '_tracking_number' );
	$tracking_carrier = $order->get_meta( '_tracking_carrier' );

	$timeline = array();
	if ( ! $is_stopped ) {
		$reached = array(
			'placed'     => true,
			'processing' => in_array( $status, array( 'processing', 'completed' ), true ),
			'shipped'    => 'completed' === $status || (bool) $tracking_number,
			'delivered'  => 'completed' === $status,
		);
		$labels = array( 'placed' => 'Order Placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered' );
		foreach ( $labels as $key => $label ) {
			$timeline[] = array( 'key' => $key, 'label' => $label, 'reached' => $reached[ $key ], 'is_current' => false );
		}
		$current_key = 'placed';
		foreach ( $timeline as $step ) { if ( $step['reached'] ) $current_key = $step['key']; }
		foreach ( $timeline as &$step ) { $step['is_current'] = ( $step['key'] === $current_key ); }
		unset( $step );
	}

	return array(
		'id'                => $order->get_id(),
		'number'            => $order->get_order_number(),
		'status'            => $status,
		'status_label'      => wc_get_order_status_name( $status ),
		'is_stopped'        => $is_stopped,
		'stopped_message'   => $is_stopped ? $stopped_messages[ $status ] : '',
		'timeline'          => $timeline,
		'tracking_number'   => $tracking_number ?: '',
		'tracking_carrier'  => $tracking_carrier ?: '',
		'date_created'      => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : null,
		'currency'          => $order->get_currency(),
		'subtotal'          => $order->get_subtotal(),
		'shipping_total'    => $order->get_shipping_total(),
		'total_tax'         => $order->get_total_tax(),
		'total'             => $order->get_total(),
		'payment_method_title' => $order->get_payment_method_title(),
		'billing_address'   => $order->get_formatted_billing_address(),
		'shipping_address'  => $order->get_formatted_shipping_address(),
		'items'             => $items,
	);
}

function oraandstone_format_customer( WC_Customer $customer ) {
	return array(
		'first_name' => $customer->get_first_name(),
		'last_name'  => $customer->get_last_name(),
		'email'      => $customer->get_email(),
		'billing'    => array(
			'first_name' => $customer->get_billing_first_name(),
			'last_name'  => $customer->get_billing_last_name(),
			'company'    => $customer->get_billing_company(),
			'address_1'  => $customer->get_billing_address_1(),
			'address_2'  => $customer->get_billing_address_2(),
			'city'       => $customer->get_billing_city(),
			'state'      => $customer->get_billing_state(),
			'postcode'   => $customer->get_billing_postcode(),
			'country'    => $customer->get_billing_country(),
			'phone'      => $customer->get_billing_phone(),
			'email'      => $customer->get_billing_email(),
		),
		'shipping' => array(
			'first_name' => $customer->get_shipping_first_name(),
			'last_name'  => $customer->get_shipping_last_name(),
			'company'    => $customer->get_shipping_company(),
			'address_1'  => $customer->get_shipping_address_1(),
			'address_2'  => $customer->get_shipping_address_2(),
			'city'       => $customer->get_shipping_city(),
			'state'      => $customer->get_shipping_state(),
			'postcode'   => $customer->get_shipping_postcode(),
			'country'    => $customer->get_shipping_country(),
			'phone'      => $customer->get_shipping_phone(),
		),
	);
}
