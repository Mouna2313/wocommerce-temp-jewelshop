<?php
/**
 * Single product gallery.
 *
 * Adapted from woocommerce/templates/single-product/product-image.php.
 * Replaces WooCommerce's default zoom/flexslider/PhotoSwipe gallery with
 * a simple main-image + thumbnail-rail layout so the .loupe cursor-zoom
 * (js/loupe.js) can be wired directly onto the active image.
 *
 * @package Ora_And_Stone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $product;

$attachment_ids = array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) );

if ( empty( $attachment_ids ) ) {
	?>
	<div class="product-gallery">
		<div class="product-gallery__main loupe" id="product-gallery-main">
			<?php echo wc_placeholder_img( 'full', array( 'class' => 'product-gallery__image' ) ); // phpcs:ignore ?>
		</div>
	</div>
	<?php
	return;
}

$main_id       = reset( $attachment_ids );
$main_full_src = wp_get_attachment_image_url( $main_id, 'full' );
?>

<div class="product-gallery">

	<div class="product-gallery__main loupe" id="product-gallery-main">
		<?php if ( $product->is_on_sale() ) : ?>
			<span class="product-gallery__badge"><span class="badge badge--gold"><?php esc_html_e( 'Sale', 'oraandstone' ); ?></span></span>
		<?php endif; ?>
		<?php
		echo wp_get_attachment_image( $main_id, 'full', false, array(
			'class'    => 'product-gallery__image',
			'data-full' => esc_url( $main_full_src ),
		) ); // phpcs:ignore
		?>
	</div>

	<?php if ( count( $attachment_ids ) > 1 ) : ?>
		<div class="product-gallery__thumbs" role="tablist" aria-label="<?php esc_attr_e( 'Product images', 'oraandstone' ); ?>">
			<?php foreach ( $attachment_ids as $i => $id ) :
				$thumb_src = wp_get_attachment_image_url( $id, 'woocommerce_gallery_thumbnail' );
				$full_src  = wp_get_attachment_image_url( $id, 'full' );
				$alt       = get_post_meta( $id, '_wp_attachment_image_alt', true );
				?>
				<button
					type="button"
					class="product-gallery__thumb<?php echo 0 === $i ? ' is-active' : ''; ?>"
					data-full="<?php echo esc_url( $full_src ); ?>"
					data-alt="<?php echo esc_attr( $alt ); ?>"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %d: image number */ __( 'View image %d', 'oraandstone' ), $i + 1 ) ); ?>"
					aria-pressed="<?php echo 0 === $i ? 'true' : 'false'; ?>"
				>
					<img src="<?php echo esc_url( $thumb_src ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy">
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

</div>
