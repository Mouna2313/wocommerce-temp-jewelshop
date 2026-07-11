<?php
/**
 * Product card, used in the shop grid, related products, and anywhere
 * else WooCommerce renders the product loop (e.g. the homepage
 * [best_selling_products] shortcode).
 *
 * Adapted from woocommerce/templates/content-product.php — same hooks,
 * markup rebuilt to the theme's product-card BEM classes.
 *
 * @package Ora_And_Stone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $product;

if ( ! $product || ! $product->is_visible() ) return;
?>
<li <?php wc_product_class( 'product-card', $product ); ?>>
	<?php do_action( 'woocommerce_before_shop_loop_item' ); ?>

	<div class="product-card__media loupe">
		<a class="product-card__media-link" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore ?>
		</a>

		<?php
		$is_new = ( time() - get_post_time( 'U', true, $product->get_id() ) ) < ( 30 * DAY_IN_SECONDS );
		if ( $product->is_on_sale() || ! $product->is_in_stock() || $is_new ) :
		?>
			<span class="product-card__badge">
				<?php if ( ! $product->is_in_stock() ) : ?>
					<span class="badge badge--sold"><?php esc_html_e( 'Sold Out', 'oraandstone' ); ?></span>
				<?php else : ?>
					<?php if ( $product->is_on_sale() ) : ?>
						<span class="badge badge--sale"><?php esc_html_e( 'Sale', 'oraandstone' ); ?></span>
					<?php endif; ?>
					<?php if ( $is_new ) : ?>
						<span class="badge badge--new"><?php esc_html_e( 'New', 'oraandstone' ); ?></span>
					<?php endif; ?>
				<?php endif; ?>
			</span>
		<?php endif; ?>

		<?php oraandstone_wishlist_button( $product->get_id() ); ?>
	</div>

	<a class="product-card__link" href="<?php the_permalink(); ?>">
		<h3 class="product-card__name"><?php echo esc_html( $product->get_name() ); ?></h3>
		<span class="product-card__price"><?php echo $product->get_price_html(); // phpcs:ignore ?></span>
	</a>

	<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>
</li>
