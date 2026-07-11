<?php
/**
 * Empty cart.
 *
 * Adapted from woocommerce/templates/cart/cart-empty.php.
 *
 * @package Ora_And_Stone
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="cart-empty-state">
	<p class="cart-empty-state__message"><?php esc_html_e( 'Your cart is currently empty.', 'oraandstone' ); ?></p>

	<?php do_action( 'woocommerce_cart_is_empty' ); ?>

	<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
		<a class="btn btn--primary" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php esc_html_e( 'Continue Shopping', 'oraandstone' ); ?>
		</a>
	<?php endif; ?>
</div>
