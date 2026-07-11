<?php
/**
 * Single product content.
 *
 * Adapted from woocommerce/templates/content-single-product.php. Structure
 * and hooks are untouched (so plugins/WC core behave normally); the
 * gallery, add-to-cart summary, and tabs are restyled via woocommerce.css
 * and the product-image.php gallery override.
 *
 * @package Ora_And_Stone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $product;

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore
	return;
}
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'product-single', $product ); ?>>

	<div class="product-single__layout">

		<div class="product-single__gallery">
			<?php do_action( 'woocommerce_before_single_product_summary' ); ?>
		</div>

		<div class="summary entry-summary product-single__summary">
			<?php do_action( 'woocommerce_single_product_summary' ); ?>
		</div>

	</div>

	<?php do_action( 'woocommerce_after_single_product_summary' ); ?>

</div>
