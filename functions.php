<?php
/**
 * Ora & Stone theme functions.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once get_stylesheet_directory() . '/inc/attributes.php';
require_once get_stylesheet_directory() . '/inc/wishlist.php';
require_once get_stylesheet_directory() . '/inc/orders.php';

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
	// Deliberately no wc-product-gallery-zoom/lightbox/slider support: the
	// theme ships its own gallery (woocommerce/single-product/product-image.php)
	// wired to the .loupe cursor-zoom instead of WooCommerce's default
	// PhotoSwipe/Flexslider gallery.

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

	if ( class_exists( 'WooCommerce' ) && is_product() ) {
		wp_enqueue_script( 'oraandstone-product-gallery', $theme_uri . '/js/product-gallery.js', array(), $version, true );
	}

	// WooCommerce-specific overrides, only on shop/product/cart/checkout/account pages
	if ( class_exists( 'WooCommerce' ) && ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() || is_product() || is_cart() || is_checkout() || is_account_page() ) ) {
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

// content-product.php builds its own <a> wrapper (.product-card__link) instead
// of WooCommerce's default, so drop the default link open/close — everything
// else on these hooks (add-to-cart button, etc.) still fires normally.
remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );

// Same reasoning on the single product page: the gallery override renders
// its own sale badge, so drop WooCommerce's default "onsale" flash to avoid
// showing it twice.
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );

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

/* ---------------------------------------------------------
 * Single product: wishlist button under the add-to-cart form
 * ------------------------------------------------------- */
function oraandstone_single_product_wishlist_button() {
	oraandstone_wishlist_button( get_the_ID(), array( 'context' => 'single' ) );
}
add_action( 'woocommerce_single_product_summary', 'oraandstone_single_product_wishlist_button', 35 );
