<?php
/**
 * Address book overview.
 *
 * Adapted from woocommerce/templates/myaccount/my-address.php.
 *
 * @package Ora_And_Stone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$customer_id = get_current_user_id();

if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
	$get_addresses = apply_filters( 'woocommerce_my_account_get_addresses', array(
		'billing'  => __( 'Billing address', 'woocommerce' ),
		'shipping' => __( 'Shipping address', 'woocommerce' ),
	), $customer_id );
} else {
	$get_addresses = apply_filters( 'woocommerce_my_account_get_addresses', array(
		'billing' => __( 'Billing address', 'woocommerce' ),
	), $customer_id );
}
?>

<p><?php esc_html_e( 'The following addresses will be used on the checkout page by default.', 'woocommerce' ); ?></p>

<div class="woocommerce-Addresses address-book<?php echo 1 === count( $get_addresses ) ? ' address-book--single' : ''; ?>">
	<?php foreach ( $get_addresses as $name => $address_title ) : ?>

		<div class="woocommerce-Address address-book__card">
			<header class="woocommerce-Address-title title address-book__card-head">
				<h3><?php echo esc_html( $address_title ); ?></h3>
				<?php $formatted_address = wc_get_account_formatted_address( $name ); ?>
				<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', $name ) ); ?>" class="edit"><?php echo $formatted_address ? esc_html__( 'Edit', 'woocommerce' ) : esc_html__( 'Add', 'woocommerce' ); ?></a>
			</header>
			<address>
				<?php echo $formatted_address ? wp_kses_post( $formatted_address ) : esc_html__( 'You have not set up this type of address yet.', 'woocommerce' ); ?>
			</address>
		</div>

	<?php endforeach; ?>
</div>
