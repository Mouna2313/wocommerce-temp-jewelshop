<?php
/**
 * Shop / category archive.
 *
 * Adapted from woocommerce/templates/archive-product.php. Adds a filter
 * sidebar (metal type + gemstone attribute layered nav, and the price
 * range widget) beside the product grid.
 *
 * @package Ora_And_Stone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' );

$oraandstone_active_filters = ! empty( $_GET['min_price'] ) || ! empty( $_GET['max_price'] )
	|| ! empty( array_filter( wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_name' ), function( $attr ) {
		return ! empty( $_GET[ 'filter_' . $attr ] );
	} ) );
?>

<div class="shop-layout">

	<details class="shop-filters" open>
		<summary class="shop-filters__summary label"><?php esc_html_e( 'Filter', 'oraandstone' ); ?></summary>

		<div class="shop-filters__body">

		<?php if ( $oraandstone_active_filters ) : ?>
			<a class="shop-filters__clear" href="<?php echo esc_url( strtok( (string) wc_get_page_permalink( 'shop' ), '?' ) ); ?>"><?php esc_html_e( 'Clear all filters', 'oraandstone' ); ?></a>
		<?php endif; ?>

		<?php
		$oraandstone_filter_groups = array(
			'metal'    => __( 'Metal', 'oraandstone' ),
			'gemstone' => __( 'Gemstone', 'oraandstone' ),
		);
		foreach ( $oraandstone_filter_groups as $oraandstone_attr_slug => $oraandstone_attr_label ) :
			if ( ! taxonomy_exists( wc_attribute_taxonomy_name( $oraandstone_attr_slug ) ) ) continue;

			ob_start();
			the_widget( 'WC_Widget_Layered_Nav', array(
				'title'        => $oraandstone_attr_label,
				'attribute'    => $oraandstone_attr_slug,
				'display_type' => 'list',
				'query_type'   => 'and',
			), array(
				'before_widget' => '<div class="widget shop-filters__group">',
				'after_widget'  => '</div>',
				'before_title'  => '<h3 class="shop-filters__group-title label">',
				'after_title'   => '</h3>',
			) );
			$oraandstone_widget_html = ob_get_clean();
			echo $oraandstone_widget_html; // phpcs:ignore -- WC-escaped widget output
		endforeach;
		?>

		<?php
		ob_start();
		the_widget( 'WC_Widget_Price_Filter', array(
			'title' => __( 'Price', 'oraandstone' ),
		), array(
			'before_widget' => '<div class="widget shop-filters__group">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="shop-filters__group-title label">',
			'after_title'   => '</h3>',
		) );
		$oraandstone_price_widget_html = ob_get_clean();
		echo $oraandstone_price_widget_html; // phpcs:ignore -- WC-escaped widget output
		?>

		</div>
	</details>

	<div class="shop-results">

		<header class="woocommerce-products-header">
			<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
				<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
			<?php endif; ?>
			<?php do_action( 'woocommerce_archive_description' ); ?>
		</header>

		<?php if ( woocommerce_product_loop() ) : ?>

			<div class="shop-toolbar">
				<?php do_action( 'woocommerce_before_shop_loop' ); ?>
			</div>

			<?php woocommerce_product_loop_start(); ?>

			<?php if ( wc_get_loop_prop( 'total' ) ) : ?>
				<?php while ( have_posts() ) : the_post(); ?>
					<?php do_action( 'woocommerce_shop_loop' ); ?>
					<?php wc_get_template_part( 'content', 'product' ); ?>
				<?php endwhile; ?>
			<?php endif; ?>

			<?php woocommerce_product_loop_end(); ?>

			<?php do_action( 'woocommerce_after_shop_loop' ); ?>

		<?php else : ?>

			<?php do_action( 'woocommerce_no_products_found' ); ?>

		<?php endif; ?>

	</div>

</div>

<?php
do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
