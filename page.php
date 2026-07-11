<?php
/**
 * Generic page template.
 *
 * Handles any WordPress page without a more specific template
 * (page-about.php covers /about). front-page.php handles the homepage.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<article <?php post_class( 'page-content' ); ?>>

		<header class="page-header container-narrow">
			<h1 class="page-header__title"><?php the_title(); ?></h1>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="page-header__media container">
				<?php the_post_thumbnail( 'full' ); ?>
			</div>
		<?php endif; ?>

		<div class="container-narrow page-body">
			<?php the_content(); ?>
		</div>

	</article>

	<?php if ( comments_open() || get_comments_number() ) : ?>
		<div class="container-narrow">
			<?php comments_template(); ?>
		</div>
	<?php endif; ?>

<?php endwhile; ?>

<?php get_footer(); ?>
