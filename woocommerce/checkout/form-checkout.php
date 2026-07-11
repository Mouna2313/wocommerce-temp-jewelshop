<?php
/**
 * Checkout page.
 *
 * Adapted from woocommerce/templates/checkout/form-checkout.php. Kept
 * hook-driven (billing/shipping fields, shipping methods, payment methods,
 * and order review are all rendered by WooCommerce's own sub-templates via
 * these actions) so validation, AJAX totals refresh, and payment gateway
 * JS all keep working — only the wrapping layout is custom.
 *
 * @package Ora_And_Stone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

// If the checkout login/coupon forms are separate, they render via the action above.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout checkout-page" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

	<?php if ( $checkout->get_checkout_fields() ) : ?>

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div class="checkout-layout">

			<div class="checkout-layout__details">
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
				<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
			</div>

			<div class="checkout-layout__review">
				<h3 id="order_review_heading"><?php esc_html_e( 'Your Order', 'woocommerce' ); ?></h3>
				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>
			</div>

		</div>

	<?php endif; ?>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
