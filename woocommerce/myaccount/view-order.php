<?php
/**
 * Single order detail (with shipping status timeline).
 *
 * Adapted from woocommerce/templates/myaccount/view-order.php. The order
 * notes list and the line-items/totals table are unchanged (rendered via
 * the woocommerce_view_order hook, which calls WooCommerce's own
 * order-details template); the fulfillment timeline is a theme addition.
 *
 * @package Ora_And_Stone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$notes = $order->get_customer_order_notes();
?>

<p>
<?php
printf(
	/* translators: 1: order number 2: order date 3: order status */
	esc_html__( 'Order #%1$s was placed on %2$s and is currently %3$s.', 'woocommerce' ),
	'<mark class="order-number">' . esc_html( $order->get_order_number() ) . '</mark>',
	'<mark class="order-date">' . esc_html( wc_format_datetime( $order->get_date_created() ) ) . '</mark>',
	'<mark class="order-status status-' . esc_attr( $order->get_status() ) . '">' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</mark>'
);
?>
</p>

<?php oraandstone_order_timeline( $order ); ?>

<?php if ( $notes ) : ?>
	<h2 class="woocommerce-order-updates-title"><?php esc_html_e( 'Order Updates', 'oraandstone' ); ?></h2>
	<ol class="woocommerce-OrderUpdates woocommerce-order-updates">
		<?php foreach ( $notes as $note ) : ?>
			<li class="woocommerce-OrderUpdate">
				<time datetime="<?php echo esc_attr( $note->date_created->date( 'c' ) ); ?>" class="woocommerce-OrderUpdate-time"><?php echo esc_html( $note->date_created->date_i18n( wc_date_format() ) ); ?></time>
				<p class="woocommerce-OrderUpdate-text"><?php echo wp_kses_post( wpautop( $note->content ) ); ?></p>
			</li>
		<?php endforeach; ?>
	</ol>
<?php endif; ?>

<?php do_action( 'woocommerce_view_order', $order_id ); ?>
