<?php
/**
 * My Account page (logged-in wrapper: nav + endpoint content).
 *
 * Adapted from woocommerce/templates/myaccount/my-account.php — only adds
 * the .account-layout grid wrapper around the standard navigation/content
 * actions; endpoint routing is untouched.
 *
 * @package Ora_And_Stone
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="account-layout">

	<?php do_action( 'woocommerce_account_navigation' ); ?>

	<div class="woocommerce-MyAccount-content">
		<?php do_action( 'woocommerce_account_content' ); ?>
	</div>

</div>
