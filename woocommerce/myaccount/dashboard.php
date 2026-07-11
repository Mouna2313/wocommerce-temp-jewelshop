<?php
/**
 * My Account dashboard.
 *
 * Adapted from woocommerce/templates/myaccount/dashboard.php, expanded
 * with quick-link cards and a recent-orders preview.
 *
 * @package Ora_And_Stone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$current_user = wp_get_current_user();
?>

<div class="account-dashboard">

	<p class="account-dashboard__welcome">
		<?php
		printf(
			/* translators: %s: user display name */
			esc_html__( 'Welcome back, %s.', 'oraandstone' ),
			'<strong>' . esc_html( $current_user->display_name ) . '</strong>'
		);
		?>
		<a class="account-dashboard__logout" href="<?php echo esc_url( wc_logout_url() ); ?>"><?php esc_html_e( 'Log out', 'oraandstone' ); ?></a>
	</p>

	<div class="account-dashboard__cards">
		<a class="account-card account-card--gold" href="<?php echo esc_url( wc_get_endpoint_url( 'orders' ) ); ?>">
			<h3><?php esc_html_e( 'Orders', 'oraandstone' ); ?></h3>
			<p><?php esc_html_e( 'Track and review past purchases.', 'oraandstone' ); ?></p>
		</a>
		<a class="account-card account-card--sapphire" href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'billing' ) ); ?>">
			<h3><?php esc_html_e( 'Addresses', 'oraandstone' ); ?></h3>
			<p><?php esc_html_e( 'Manage shipping and billing details.', 'oraandstone' ); ?></p>
		</a>
		<a class="account-card account-card--emerald" href="<?php echo esc_url( wc_get_endpoint_url( 'edit-account' ) ); ?>">
			<h3><?php esc_html_e( 'Account Details', 'oraandstone' ); ?></h3>
			<p><?php esc_html_e( 'Update your name, email, and password.', 'oraandstone' ); ?></p>
		</a>
		<a class="account-card account-card--ruby" href="<?php echo esc_url( oraandstone_wishlist_get_url() ); ?>">
			<h3><?php esc_html_e( 'Wishlist', 'oraandstone' ); ?></h3>
			<p><?php esc_html_e( 'Saved pieces you\'re thinking about.', 'oraandstone' ); ?></p>
		</a>
	</div>

	<?php
	$recent_orders = wc_get_orders( array(
		'customer' => $current_user->ID,
		'limit'    => 3,
		'orderby'  => 'date',
		'order'    => 'DESC',
	) );
	?>

	<?php if ( $recent_orders ) : ?>
		<div class="account-dashboard__recent">
			<div class="section__head">
				<h3><?php esc_html_e( 'Recent Orders', 'oraandstone' ); ?></h3>
				<a class="btn btn--ghost" href="<?php echo esc_url( wc_get_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'View All →', 'oraandstone' ); ?></a>
			</div>

			<?php foreach ( $recent_orders as $order ) : ?>
				<a class="account-order-row" href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
					<span class="account-order-row__number">#<?php echo esc_html( $order->get_order_number() ); ?></span>
					<span class="account-order-row__date"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
					<mark class="order-status status-<?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></mark>
					<span class="account-order-row__total"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

</div>
