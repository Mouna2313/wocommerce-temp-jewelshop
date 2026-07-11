<?php
/**
 * About / Brand Story page.
 *
 * Expands the .story-teaser pattern used on the homepage into a full
 * page: an intro band, two alternating story-teaser sections, a values
 * grid, a stats band, and a closing CTA back to the shop.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<!-- Intro -->
	<section class="section about-intro container-narrow">
		<span class="label"><?php esc_html_e( 'Since 2016', 'oraandstone' ); ?></span>
		<h1><?php the_title(); ?></h1>
		<p class="about-intro__lead">
			<?php esc_html_e( 'Ora & Stone started on a jeweler\'s bench, not a spreadsheet. We design solid-gold, responsibly sourced pieces meant to be worn daily and handed down — and we still make every piece the same way we made the first one.', 'oraandstone' ); ?>
		</p>
	</section>

	<?php if ( get_the_content() ) : ?>
		<div class="container-narrow page-body">
			<?php the_content(); ?>
		</div>
	<?php endif; ?>

	<hr class="hr-gold container">

	<!-- Story teaser: workshop -->
	<section class="section container">
		<div class="story-teaser">
			<div class="story-teaser__media loupe">
				<img src="<?php echo esc_url( get_theme_file_uri( '/assets/about-workshop.jpg' ) ); ?>" alt="<?php esc_attr_e( 'Goldsmith at the bench, shaping a ring setting', 'oraandstone' ); ?>">
			</div>
			<div class="story-teaser__copy">
				<span class="label"><?php esc_html_e( 'From Bench to Box', 'oraandstone' ); ?></span>
				<blockquote><?php esc_html_e( '“Every piece starts on the bench, not the screen.”', 'oraandstone' ); ?></blockquote>
				<p><?php esc_html_e( 'We work with a small studio of goldsmiths who hand-finish every setting. No casting shortcuts, no outsourced polishing — the same three hands touch a piece from raw metal to the box it ships in.', 'oraandstone' ); ?></p>
			</div>
		</div>
	</section>

	<!-- Values -->
	<section class="section section--alt">
		<div class="container">
			<div class="section__head">
				<div>
					<span class="label"><?php esc_html_e( 'What We Won\'t Compromise On', 'oraandstone' ); ?></span>
					<h2><?php esc_html_e( 'How We Work', 'oraandstone' ); ?></h2>
				</div>
			</div>
			<div class="about-values">
				<div class="about-values__item">
					<h3><?php esc_html_e( 'Ethically Sourced', 'oraandstone' ); ?></h3>
					<p><?php esc_html_e( 'Recycled gold and traceable, conflict-free stones — every material has a paper trail back to its origin.', 'oraandstone' ); ?></p>
				</div>
				<div class="about-values__item">
					<h3><?php esc_html_e( 'Handcrafted', 'oraandstone' ); ?></h3>
					<p><?php esc_html_e( 'Each piece is shaped, set, and polished by hand in a studio of six goldsmiths, not a factory line.', 'oraandstone' ); ?></p>
				</div>
				<div class="about-values__item">
					<h3><?php esc_html_e( 'Made to Last', 'oraandstone' ); ?></h3>
					<p><?php esc_html_e( 'Solid metals, not plated. Every piece comes with complimentary resizing and repairs for life.', 'oraandstone' ); ?></p>
				</div>
			</div>
		</div>
	</section>

	<!-- Story teaser: sourcing (reversed) -->
	<section class="section container">
		<div class="story-teaser story-teaser--reverse">
			<div class="story-teaser__copy">
				<span class="label"><?php esc_html_e( 'Responsibly Sourced', 'oraandstone' ); ?></span>
				<blockquote><?php esc_html_e( '“We can trace every stone back to the mine it came from.”', 'oraandstone' ); ?></blockquote>
				<p><?php esc_html_e( 'We buy in small batches directly from suppliers we\'ve worked with for years, and we ask the questions most jewelers don\'t: who mined this, and under what conditions.', 'oraandstone' ); ?></p>
			</div>
			<div class="story-teaser__media loupe">
				<img src="<?php echo esc_url( get_theme_file_uri( '/assets/about-sourcing.jpg' ) ); ?>" alt="<?php esc_attr_e( 'Loose gemstones being sorted by hand', 'oraandstone' ); ?>">
			</div>
		</div>
	</section>

	<!-- Stats -->
	<section class="section about-stats-band">
		<div class="container about-stats">
			<div class="about-stats__item">
				<span class="about-stats__number">9</span>
				<span class="label"><?php esc_html_e( 'Years in the Workshop', 'oraandstone' ); ?></span>
			</div>
			<div class="about-stats__item">
				<span class="about-stats__number">100%</span>
				<span class="label"><?php esc_html_e( 'Recycled Gold', 'oraandstone' ); ?></span>
			</div>
			<div class="about-stats__item">
				<span class="about-stats__number">6</span>
				<span class="label"><?php esc_html_e( 'Goldsmiths on Staff', 'oraandstone' ); ?></span>
			</div>
			<div class="about-stats__item">
				<span class="about-stats__number">&infin;</span>
				<span class="label"><?php esc_html_e( 'Repairs for Life', 'oraandstone' ); ?></span>
			</div>
		</div>
	</section>

	<!-- Closing CTA -->
	<section class="section container about-cta">
		<h2><?php esc_html_e( 'Find the Piece You\'ll Wear Every Day', 'oraandstone' ); ?></h2>
		<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="btn btn--vibrant"><?php esc_html_e( 'Shop the Collection', 'oraandstone' ); ?></a>
	</section>

<?php endwhile; ?>

<?php get_footer(); ?>
