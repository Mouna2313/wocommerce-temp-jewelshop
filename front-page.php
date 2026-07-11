<?php
/**
 * Homepage template.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<!-- Hero -->
<section class="container hero">
	<div class="hero__copy">
		<span class="label"><?php esc_html_e( 'New Season · Autumn Light Collection', 'oraandstone' ); ?></span>
		<h1 class="hero__title"><?php esc_html_e( 'Jewelry that catches the light you already have.', 'oraandstone' ); ?></h1>
		<p class="hero__sub"><?php esc_html_e( 'Solid gold and ethically sourced stones, designed to be worn daily and passed down.', 'oraandstone' ); ?></p>
		<div class="hero__actions">
			<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="btn btn--vibrant"><?php esc_html_e( 'Shop the Collection', 'oraandstone' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/about' ) ); ?>" class="btn btn--outline"><?php esc_html_e( 'Our Story', 'oraandstone' ); ?></a>
		</div>
	</div>
	<div class="hero__media loupe">
		<img src="<?php echo esc_url( get_theme_file_uri( '/assets/hero-placeholder.jpg' ) ); ?>" alt="<?php esc_attr_e( 'Model wearing a gold ring and layered necklaces', 'oraandstone' ); ?>">
	</div>
</section>

<hr class="hr-gold container">

<!-- Featured collections -->
<section class="section container">
	<div class="section__head">
		<div>
			<span class="label"><?php esc_html_e( 'Curated', 'oraandstone' ); ?></span>
			<h2><?php esc_html_e( 'Shop by Collection', 'oraandstone' ); ?></h2>
		</div>
		<a href="<?php echo esc_url( home_url( '/collections' ) ); ?>" class="btn btn--ghost"><?php esc_html_e( 'View All →', 'oraandstone' ); ?></a>
	</div>
	<div class="collections">
		<a class="collection-card" href="<?php echo esc_url( home_url( '/shop/rings' ) ); ?>">
			<img src="<?php echo esc_url( get_theme_file_uri( '/assets/collection-rings.jpg' ) ); ?>" alt="<?php esc_attr_e( 'Rings collection', 'oraandstone' ); ?>">
			<span class="collection-card__label"><?php esc_html_e( 'Rings', 'oraandstone' ); ?></span>
		</a>
		<a class="collection-card" href="<?php echo esc_url( home_url( '/shop/necklaces' ) ); ?>">
			<img src="<?php echo esc_url( get_theme_file_uri( '/assets/collection-necklaces.jpg' ) ); ?>" alt="<?php esc_attr_e( 'Necklaces collection', 'oraandstone' ); ?>">
			<span class="collection-card__label"><?php esc_html_e( 'Necklaces', 'oraandstone' ); ?></span>
		</a>
		<a class="collection-card" href="<?php echo esc_url( home_url( '/shop/earrings' ) ); ?>">
			<img src="<?php echo esc_url( get_theme_file_uri( '/assets/collection-earrings.jpg' ) ); ?>" alt="<?php esc_attr_e( 'Earrings collection', 'oraandstone' ); ?>">
			<span class="collection-card__label"><?php esc_html_e( 'Earrings', 'oraandstone' ); ?></span>
		</a>
	</div>
</section>

<!-- Bestsellers (WooCommerce shortcode-driven) -->
<section class="section section--alt">
	<div class="container">
		<div class="section__head">
			<div>
				<span class="label"><?php esc_html_e( 'Most Loved', 'oraandstone' ); ?></span>
				<h2><?php esc_html_e( 'Bestsellers', 'oraandstone' ); ?></h2>
			</div>
			<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="btn btn--ghost"><?php esc_html_e( 'View All →', 'oraandstone' ); ?></a>
		</div>

		<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<div class="product-grid">
				<?php echo do_shortcode( '[best_selling_products limit="4"]' ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<!-- Brand story teaser -->
<section class="section container">
	<div class="story-teaser">
		<div class="story-teaser__media loupe">
			<img src="<?php echo esc_url( get_theme_file_uri( '/assets/story-placeholder.jpg' ) ); ?>" alt="<?php esc_attr_e( 'Jeweler at work on a gold setting', 'oraandstone' ); ?>">
		</div>
		<div class="story-teaser__copy">
			<span class="label"><?php esc_html_e( 'Since 2016', 'oraandstone' ); ?></span>
			<blockquote><?php esc_html_e( '“Every piece starts on the bench, not the screen.”', 'oraandstone' ); ?></blockquote>
			<p><?php esc_html_e( 'We work with a small studio of goldsmiths to make jewelry meant for everyday wear — solid metals, responsibly sourced stones, and repairs for life.', 'oraandstone' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/about' ) ); ?>" class="btn btn--outline"><?php esc_html_e( 'Read Our Story', 'oraandstone' ); ?></a>
		</div>
	</div>
</section>

<!-- Newsletter -->
<section class="newsletter">
	<div class="container-narrow">
		<h2><?php esc_html_e( 'Join the Ora & Stone list', 'oraandstone' ); ?></h2>
		<p><?php esc_html_e( 'New arrivals, restocks, and the occasional invitation to see the workshop.', 'oraandstone' ); ?></p>
		<form class="newsletter-form" method="post" action="#">
			<label class="visually-hidden" for="newsletter-email"><?php esc_html_e( 'Email address', 'oraandstone' ); ?></label>
			<input type="email" id="newsletter-email" name="email" placeholder="<?php esc_attr_e( 'Your email', 'oraandstone' ); ?>" required>
			<button type="submit" class="btn btn--vibrant"><?php esc_html_e( 'Sign Up', 'oraandstone' ); ?></button>
		</form>
	</div>
</section>

<?php get_footer(); ?>
