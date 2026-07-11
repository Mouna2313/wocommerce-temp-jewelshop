<?php
/**
 * Related products.
 *
 * Adapted from woocommerce/templates/single-product/related.php — wraps
 * the default related-products loop in the theme's .section pattern
 * (matching the "Bestsellers" section on the homepage) instead of WC's
 * bare <section class="related products">.
 *
 * @package Ora_And_Stone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! $related_products ) return;
?>
<hr class="hr-gold">
<section class="section related products">
	<div class="section__head">
		<div>
			<span class="label"><?php esc_html_e( 'You May Also Like', 'oraandstone' ); ?></span>
			<h2><?php esc_html_e( 'Related Pieces', 'oraandstone' ); ?></h2>
		</div>
	</div>

	<?php woocommerce_product_loop_start(); ?>

	<?php foreach ( $related_products as $related_product ) :
		$post_object = get_post( $related_product->get_id() );

		setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore

		wc_get_template_part( 'content', 'product' );
	endforeach; ?>

	<?php woocommerce_product_loop_end(); ?>

</section>
<?php
wp_reset_postdata();
