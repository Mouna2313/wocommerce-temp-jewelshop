<?php
/**
 * Custom wishlist functionality.
 *
 * Used only when a wishlist plugin (e.g. YITH WooCommerce Wishlist) is not
 * active. Stores product IDs in user meta for logged-in shoppers and in a
 * cookie for guests, with a small AJAX endpoint for add/remove buttons on
 * product cards and the single product page.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ORAANDSTONE_WISHLIST_COOKIE', 'oraandstone_wishlist' );
define( 'ORAANDSTONE_WISHLIST_META', '_oraandstone_wishlist' );

function oraandstone_wishlist_plugin_active() {
	return class_exists( 'YITH_WCWL' );
}

/* ---------------------------------------------------------
 * Storage
 * ------------------------------------------------------- */
function oraandstone_wishlist_get_ids() {
	if ( is_user_logged_in() ) {
		$ids = get_user_meta( get_current_user_id(), ORAANDSTONE_WISHLIST_META, true );
		$ids = is_array( $ids ) ? $ids : array();
	} else {
		$ids = array();
		if ( ! empty( $_COOKIE[ ORAANDSTONE_WISHLIST_COOKIE ] ) ) {
			$ids = array_map( 'absint', explode( ',', wp_unslash( $_COOKIE[ ORAANDSTONE_WISHLIST_COOKIE ] ) ) );
		}
	}
	return array_values( array_unique( array_filter( $ids ) ) );
}

function oraandstone_wishlist_save_ids( $ids ) {
	$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), ORAANDSTONE_WISHLIST_META, $ids );
	} else {
		setcookie(
			ORAANDSTONE_WISHLIST_COOKIE,
			implode( ',', $ids ),
			time() + ( DAY_IN_SECONDS * 30 ),
			COOKIEPATH ? COOKIEPATH : '/',
			COOKIE_DOMAIN
		);
		$_COOKIE[ ORAANDSTONE_WISHLIST_COOKIE ] = implode( ',', $ids );
	}

	return $ids;
}

function oraandstone_wishlist_has( $product_id ) {
	return in_array( (int) $product_id, oraandstone_wishlist_get_ids(), true );
}

function oraandstone_wishlist_toggle( $product_id ) {
	$product_id = absint( $product_id );
	$ids        = oraandstone_wishlist_get_ids();

	if ( in_array( $product_id, $ids, true ) ) {
		$ids   = array_diff( $ids, array( $product_id ) );
		$added = false;
	} else {
		$ids[]  = $product_id;
		$added = true;
	}

	oraandstone_wishlist_save_ids( $ids );

	return $added;
}

function oraandstone_wishlist_count() {
	return count( oraandstone_wishlist_get_ids() );
}

// Merge a guest's cookie wishlist into their account the moment they log in.
function oraandstone_wishlist_merge_on_login( $user_login, $user ) {
	if ( empty( $_COOKIE[ ORAANDSTONE_WISHLIST_COOKIE ] ) ) return;

	$guest_ids = array_map( 'absint', explode( ',', wp_unslash( $_COOKIE[ ORAANDSTONE_WISHLIST_COOKIE ] ) ) );
	$user_ids  = get_user_meta( $user->ID, ORAANDSTONE_WISHLIST_META, true );
	$user_ids  = is_array( $user_ids ) ? $user_ids : array();

	update_user_meta( $user->ID, ORAANDSTONE_WISHLIST_META, array_values( array_unique( array_filter( array_merge( $user_ids, $guest_ids ) ) ) ) );

	setcookie( ORAANDSTONE_WISHLIST_COOKIE, '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
}
add_action( 'wp_login', 'oraandstone_wishlist_merge_on_login', 10, 2 );

/* ---------------------------------------------------------
 * AJAX
 * ------------------------------------------------------- */
function oraandstone_wishlist_ajax_toggle() {
	check_ajax_referer( 'oraandstone-wishlist', 'nonce' );

	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	if ( ! $product_id || ! get_post( $product_id ) ) {
		wp_send_json_error();
	}

	$added = oraandstone_wishlist_toggle( $product_id );

	wp_send_json_success( array(
		'in_wishlist' => $added,
		'count'       => oraandstone_wishlist_count(),
	) );
}
add_action( 'wp_ajax_oraandstone_toggle_wishlist', 'oraandstone_wishlist_ajax_toggle' );
add_action( 'wp_ajax_nopriv_oraandstone_toggle_wishlist', 'oraandstone_wishlist_ajax_toggle' );

/* ---------------------------------------------------------
 * Template helpers
 * ------------------------------------------------------- */
function oraandstone_wishlist_button( $product_id, $args = array() ) {
	$product_id = absint( $product_id );
	if ( ! $product_id ) return;

	// Defer to the YITH wishlist plugin's own button when it's active.
	if ( oraandstone_wishlist_plugin_active() ) {
		echo do_shortcode( '[yith_wcwl_add_to_wishlist product_id="' . $product_id . '"]' );
		return;
	}

	$defaults = array( 'context' => 'card' ); // 'card' | 'single'
	$args     = wp_parse_args( $args, $defaults );
	$active   = oraandstone_wishlist_has( $product_id );
	$label    = $active ? __( 'Remove from wishlist', 'oraandstone' ) : __( 'Add to wishlist', 'oraandstone' );
	$show_text = 'single' === $args['context'];
	?>
	<button
		type="button"
		class="wishlist-btn wishlist-btn--<?php echo esc_attr( $args['context'] ); ?><?php echo $active ? ' is-active' : ''; ?>"
		data-product-id="<?php echo esc_attr( $product_id ); ?>"
		aria-pressed="<?php echo $active ? 'true' : 'false'; ?>"
		<?php echo $show_text ? '' : 'aria-label="' . esc_attr( $label ) . '"'; ?>
	>
		<svg width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $active ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
			<path d="M12 21s-7-4.35-9.5-8.5C.7 8.6 2.6 5 6 5c2 0 3.5 1.2 4 2.5.5-1.3 2-2.5 4-2.5 3.4 0 5.3 3.6 3.5 7.5C19 16.65 12 21 12 21z"/>
		</svg>
		<?php if ( $show_text ) : ?>
			<span class="wishlist-btn__label"><?php echo esc_html( $label ); ?></span>
		<?php endif; ?>
	</button>
	<?php
}

/* ---------------------------------------------------------
 * Assets
 * ------------------------------------------------------- */
function oraandstone_wishlist_enqueue_assets() {
	if ( oraandstone_wishlist_plugin_active() ) return;

	$theme_uri = get_stylesheet_directory_uri();
	$version   = wp_get_theme()->get( 'Version' );

	wp_enqueue_style( 'oraandstone-wishlist', $theme_uri . '/css/wishlist.css', array( 'oraandstone-tokens' ), $version );

	wp_enqueue_script( 'oraandstone-wishlist', $theme_uri . '/js/wishlist.js', array(), $version, true );
	wp_localize_script( 'oraandstone-wishlist', 'oraandstoneWishlist', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'oraandstone-wishlist' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'oraandstone_wishlist_enqueue_assets' );
