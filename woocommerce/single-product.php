<?php
/**
 * Single product page wrapper.
 *
 * Adapted from woocommerce/templates/single-product.php — unchanged in
 * structure, kept as an override point for the theme's header/footer.
 *
 * @package Ora_And_Stone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header( 'shop' );

while ( have_posts() ) :
	the_post();
	wc_get_template_part( 'content', 'single-product' );
endwhile;

get_footer( 'shop' );
