<?php
/**
 * Header template.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main-content"><?php esc_html_e( 'Skip to content', 'oraandstone' ); ?></a>

<header class="site-header">
	<div class="container navbar">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="navbar__logo">
			Ora <span>&amp;</span> Stone
		</a>

		<nav class="navbar__menu" aria-label="<?php esc_attr_e( 'Primary', 'oraandstone' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'items_wrap'     => '%3$s',
				'fallback_cb'    => function() {
					echo '<a href="' . esc_url( home_url( '/shop' ) ) . '">Shop</a>';
					echo '<a href="' . esc_url( home_url( '/collections' ) ) . '">Collections</a>';
					echo '<a href="' . esc_url( home_url( '/about' ) ) . '">Our Story</a>';
					echo '<a href="' . esc_url( home_url( '/contact' ) ) . '">Contact</a>';
				},
			) );
			?>
		</nav>

		<div class="navbar__actions">
			<button class="navbar__icon-btn" aria-label="<?php esc_attr_e( 'Search', 'oraandstone' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
			</button>

			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="navbar__icon-btn" href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>" aria-label="<?php esc_attr_e( 'My account', 'oraandstone' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
				</a>

				<a class="navbar__icon-btn" href="<?php echo esc_url( oraandstone_wishlist_get_url() ); ?>" aria-label="<?php esc_attr_e( 'Wishlist', 'oraandstone' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 21s-7-4.35-9.5-8.5C.7 8.6 2.6 5 6 5c2 0 3.5 1.2 4 2.5.5-1.3 2-2.5 4-2.5 3.4 0 5.3 3.6 3.5 7.5C19 16.65 12 21 12 21z"/></svg>
					<?php if ( ! oraandstone_wishlist_plugin_active() ) : $oraandstone_wishlist_count = oraandstone_wishlist_count(); ?>
						<span class="navbar__badge" id="oraandstone-wishlist-count"<?php echo $oraandstone_wishlist_count < 1 ? ' hidden' : ''; ?>><?php echo esc_html( $oraandstone_wishlist_count ); ?></span>
					<?php endif; ?>
				</a>

				<a class="navbar__icon-btn" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'oraandstone' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 3h2l.4 2M7 13h10l3-7H5.4M7 13L5.4 5M7 13l-2 5h13"/><circle cx="9" cy="20" r="1"/><circle cx="17" cy="20" r="1"/></svg>
					<?php echo WC()->cart ? '<span class="navbar__badge" id="oraandstone-cart-count">' . esc_html( WC()->cart->get_cart_contents_count() ) . '</span>' : ''; ?>
				</a>
			<?php endif; ?>

			<button class="navbar__toggle" aria-label="<?php esc_attr_e( 'Open menu', 'oraandstone' ); ?>" aria-expanded="false">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
			</button>
		</div>
	</div>
</header>

<main id="main-content">
