<?php
/**
 * Registers the global product attributes the shop filter sidebar relies on
 * (Metal Type, Gemstone) if they don't already exist. Runs once on theme
 * activation — safe to re-run, it skips anything already present.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function oraandstone_setup_product_attributes() {
	if ( ! function_exists( 'wc_create_attribute' ) ) return;

	$attributes = array(
		'metal'    => __( 'Metal Type', 'oraandstone' ),
		'gemstone' => __( 'Gemstone', 'oraandstone' ),
	);

	$created = false;

	foreach ( $attributes as $slug => $label ) {
		if ( taxonomy_exists( wc_attribute_taxonomy_name( $slug ) ) ) continue;

		wc_create_attribute( array(
			'name'         => $label,
			'slug'         => $slug,
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => false,
		) );
		$created = true;
	}

	if ( $created ) {
		delete_transient( 'wc_attribute_taxonomies' );
		flush_rewrite_rules();
	}
}
add_action( 'after_switch_theme', 'oraandstone_setup_product_attributes' );
