<?php
/**
 * Ora & Stone theme functions.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------------------------------------------------------
 * Theme setup
 * ------------------------------------------------------- */
function oraandstone_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'automatic-feed-links' );

	// WooCommerce
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );   // native zoom off in favor of our loupe, but declared for compatibility
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	// Nav menus
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'oraandstone' ),
		'footer-shop'    => __( 'Footer — Shop', 'oraandstone' ),
		'footer-help'    => __( 'Footer — Help', 'oraandstone' ),
		'footer-company' => __( 'Footer — Company', 'oraandstone' ),
	) );
}
add_action( 'after_setup_theme', 'oraandstone_setup' );

/* ---------------------------------------------------------
 * Assets
 * ------------------------------------------------------- */
function oraandstone_enqueue_assets() {
	$theme_uri = get_stylesheet_directory_uri();
	$version   = wp_get_theme()->get( 'Version' );

	// Google Fonts: Fraunces (display) + Work Sans (body/utility)
	wp_enqueue_style(
		'oraandstone-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..600&family=Work+Sans:wght@400;500;600;700&display=swap',
		array(),
		null
	);

	// Design tokens must load first, everything else depends on the custom properties
	wp_enqueue_style( 'oraandstone-tokens',      $theme_uri . '/css/tokens.css', array(), $version );
	wp_enqueue_style( 'oraandstone-base',        $theme_uri . '/css/base.css', array( 'oraandstone-tokens' ), $version );
	wp_enqueue_style( 'oraandstone-buttons',     $theme_uri . '/css/buttons.css', array( 'oraandstone-tokens' ), $version );
	wp_enqueue_style( 'oraandstone-navbar',      $theme_uri . '/css/navbar.css', array( 'oraandstone-tokens' ), $version );
	wp_enqueue_style( 'oraandstone-hero',        $theme_uri . '/css/hero.css', array( 'oraandstone-tokens' ), $version );
	wp_enqueue_style( 'oraandstone-footer',      $theme_uri . '/css/footer.css', array( 'oraandstone-tokens' ), $version );

	// Base theme stylesheet (required header) loads last, empty of rules by design
	wp_enqueue_style( 'oraandstone-style', get_stylesheet_uri(), array( 'oraandstone-base' ), $version );

	wp_enqueue_script( 'oraandstone-loupe', $theme_uri . '/js/loupe.js', array(), $version, true );

	// WooCommerce-specific overrides, only on shop/product/cart/checkout/account pages
	if ( class_exists( 'WooCommerce' ) && ( is_shop() || is_product_category() || is_product() || is_cart() || is_checkout() || is_account_page() ) ) {
		wp_enqueue_style( 'oraandstone-woocommerce', $theme_uri . '/css/woocommerce.css', array( 'oraandstone-tokens' ), $version );
	}
}
add_action( 'wp_enqueue_scripts', 'oraandstone_enqueue_assets' );

/* ---------------------------------------------------------
 * Widget areas
 * ------------------------------------------------------- */
function oraandstone_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Shop Sidebar', 'oraandstone' ),
		'id'            => 'shop-sidebar',
		'before_widget' => '<div class="widget">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="label">',
		'after_title'   => '</h4>',
	) );
}
add_action( 'widgets_init', 'oraandstone_widgets_init' );

/* ---------------------------------------------------------
 * WooCommerce layout adjustments
 * Remove default wrappers so our own template markup/classes apply.
 * ------------------------------------------------------- */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

function oraandstone_wc_wrapper_start() {
	echo '<main class="container woocommerce-main">';
}
add_action( 'woocommerce_before_main_content', 'oraandstone_wc_wrapper_start' );

function oraandstone_wc_wrapper_end() {
	echo '</main>';
}
add_action( 'woocommerce_after_main_content', 'oraandstone_wc_wrapper_end' );

// Change default product grid columns to match our 4-col design
add_filter( 'loop_shop_columns', function() { return 4; } );

/* ---------------------------------------------------------
 * Cart count fragment for the navbar icon (AJAX-updated)
 * ------------------------------------------------------- */
function oraandstone_cart_count_fragment( $fragments ) {
	ob_start();
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	?>
	<span class="navbar__badge" id="oraandstone-cart-count"><?php echo esc_html( $count ); ?></span>
	<?php
	$fragments['#oraandstone-cart-count'] = ob_get_clean();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'oraandstone_cart_count_fragment' );
