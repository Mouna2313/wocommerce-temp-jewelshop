</main>

<footer class="site-footer">
	<div class="container">
		<div class="footer-grid">
			<div class="footer-brand">
				<div class="footer-brand__logo">Ora <span>&amp;</span> Stone</div>
				<p><?php esc_html_e( 'Fine jewelry, made to be worn every day and kept for a lifetime.', 'oraandstone' ); ?></p>
			</div>

			<div class="footer-col">
				<h4><?php esc_html_e( 'Shop', 'oraandstone' ); ?></h4>
				<?php
				wp_nav_menu( array(
					'theme_location' => 'footer-shop',
					'container'      => false,
					'items_wrap'     => '<ul>%3$s</ul>',
					'fallback_cb'    => function() {
						echo '<ul>
							<li><a href="' . esc_url( home_url( '/shop/rings' ) ) . '">' . esc_html__( 'Rings', 'oraandstone' ) . '</a></li>
							<li><a href="' . esc_url( home_url( '/shop/necklaces' ) ) . '">' . esc_html__( 'Necklaces', 'oraandstone' ) . '</a></li>
							<li><a href="' . esc_url( home_url( '/shop/earrings' ) ) . '">' . esc_html__( 'Earrings', 'oraandstone' ) . '</a></li>
							<li><a href="' . esc_url( home_url( '/shop/bracelets' ) ) . '">' . esc_html__( 'Bracelets', 'oraandstone' ) . '</a></li>
						</ul>';
					},
				) );
				?>
			</div>

			<div class="footer-col">
				<h4><?php esc_html_e( 'Help', 'oraandstone' ); ?></h4>
				<?php
				wp_nav_menu( array(
					'theme_location' => 'footer-help',
					'container'      => false,
					'items_wrap'     => '<ul>%3$s</ul>',
					'fallback_cb'    => function() {
						echo '<ul>
							<li><a href="' . esc_url( home_url( '/shipping-returns' ) ) . '">' . esc_html__( 'Shipping &amp; Returns', 'oraandstone' ) . '</a></li>
							<li><a href="' . esc_url( home_url( '/sizing-guide' ) ) . '">' . esc_html__( 'Sizing Guide', 'oraandstone' ) . '</a></li>
							<li><a href="' . esc_url( home_url( '/care' ) ) . '">' . esc_html__( 'Jewelry Care', 'oraandstone' ) . '</a></li>
							<li><a href="' . esc_url( home_url( '/contact' ) ) . '">' . esc_html__( 'Contact Us', 'oraandstone' ) . '</a></li>
						</ul>';
					},
				) );
				?>
			</div>

			<div class="footer-col">
				<h4><?php esc_html_e( 'Company', 'oraandstone' ); ?></h4>
				<?php
				wp_nav_menu( array(
					'theme_location' => 'footer-company',
					'container'      => false,
					'items_wrap'     => '<ul>%3$s</ul>',
					'fallback_cb'    => function() {
						echo '<ul>
							<li><a href="' . esc_url( home_url( '/about' ) ) . '">' . esc_html__( 'Our Story', 'oraandstone' ) . '</a></li>
							<li><a href="' . esc_url( home_url( '/sustainability' ) ) . '">' . esc_html__( 'Sustainability', 'oraandstone' ) . '</a></li>
							<li><a href="' . esc_url( home_url( '/journal' ) ) . '">' . esc_html__( 'Journal', 'oraandstone' ) . '</a></li>
						</ul>';
					},
				) );
				?>
			</div>
		</div>

		<div class="footer-bottom">
			<span>&copy; <?php echo esc_html( date( 'Y' ) ); ?> Ora &amp; Stone. <?php esc_html_e( 'All rights reserved.', 'oraandstone' ); ?></span>
			<span><?php esc_html_e( 'Handcrafted with care.', 'oraandstone' ); ?></span>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
