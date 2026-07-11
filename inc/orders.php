<?php
/**
 * Order detail helpers: the shipping/fulfillment status timeline shown on
 * the account order-detail page.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Renders a 4-step fulfillment timeline (Placed → Processing → Shipped →
 * Delivered) mapped from WooCommerce's core order statuses. Falls back to
 * a plain notice for cancelled/failed/refunded orders, where a
 * step-progress bar would be misleading.
 */
function oraandstone_order_timeline( $order ) {
	$status = $order->get_status();

	if ( in_array( $status, array( 'cancelled', 'failed', 'refunded' ), true ) ) {
		$messages = array(
			'cancelled' => __( 'This order was cancelled.', 'oraandstone' ),
			'failed'    => __( 'Payment failed for this order.', 'oraandstone' ),
			'refunded'  => __( 'This order was refunded.', 'oraandstone' ),
		);
		?>
		<div class="order-timeline order-timeline--stopped">
			<p><?php echo esc_html( $messages[ $status ] ); ?></p>
		</div>
		<?php
		return;
	}

	$steps = array(
		'placed'     => __( 'Order Placed', 'oraandstone' ),
		'processing' => __( 'Processing', 'oraandstone' ),
		'shipped'    => __( 'Shipped', 'oraandstone' ),
		'delivered'  => __( 'Delivered', 'oraandstone' ),
	);

	$reached = array(
		'placed'     => true,
		'processing' => in_array( $status, array( 'processing', 'completed' ), true ),
		'shipped'    => 'completed' === $status,
		'delivered'  => 'completed' === $status,
	);

	// A shipment-tracking plugin/meta can mark "shipped" independently of status.
	$tracking_number = $order->get_meta( '_tracking_number' );
	if ( $tracking_number ) {
		$reached['shipped'] = true;
	}

	$current = 'placed';
	foreach ( $steps as $key => $label ) {
		if ( $reached[ $key ] ) $current = $key;
	}
	?>
	<ol class="order-timeline">
		<?php foreach ( $steps as $key => $label ) : ?>
			<li class="order-timeline__step<?php echo $reached[ $key ] ? ' is-complete' : ''; ?><?php echo $key === $current ? ' is-current' : ''; ?>">
				<span class="order-timeline__dot" aria-hidden="true"></span>
				<span class="order-timeline__label"><?php echo esc_html( $label ); ?></span>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php
	if ( $tracking_number ) :
		$tracking_carrier = $order->get_meta( '_tracking_carrier' );
		?>
		<p class="order-timeline__tracking">
			<?php
			printf(
				/* translators: 1: carrier name (may be blank) 2: tracking number */
				esc_html__( 'Tracking: %1$s%2$s', 'oraandstone' ),
				$tracking_carrier ? esc_html( $tracking_carrier ) . ' ' : '',
				esc_html( $tracking_number )
			);
			?>
		</p>
	<?php endif;
}
