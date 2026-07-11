<?php
/**
 * Dedicated wishlist page (My Account > Wishlist).
 *
 * Only used when no wishlist plugin is active — see inc/wishlist.php.
 *
 * @package Ora_And_Stone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$wishlist_ids = oraandstone_wishlist_get_ids();
?>

<?php if ( empty( $wishlist_ids ) ) : ?>

	<div class="wishlist-page__empty">
		<p><?php esc_html_e( "You haven't saved anything yet.", 'oraandstone' ); ?></p>
		<a class="btn btn--primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Browse Products', 'oraandstone' ); ?></a>
	</div>

<?php else : ?>

	<ul class="products wishlist-grid">
		<?php
		foreach ( $wishlist_ids as $product_id ) {
			$post_object = get_post( $product_id );
			if ( ! $post_object || 'product' !== $post_object->post_type ) continue;

			setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore

			wc_get_template_part( 'content', 'product' );
		}
		wp_reset_postdata();
		?>
	</ul>

<?php endif; ?>
