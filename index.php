<?php
/**
 * Fallback template (required by every WP theme).
 * front-page.php handles the homepage; page.php / WooCommerce
 * templates handle everything else. This covers anything unmatched.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<div class="container" style="padding-block: var(--space-2xl);">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<article <?php post_class(); ?>>
			<h1><?php the_title(); ?></h1>
			<div class="entry-content"><?php the_content(); ?></div>
		</article>
	<?php endwhile; else : ?>
		<p><?php esc_html_e( 'Nothing found.', 'oraandstone' ); ?></p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
