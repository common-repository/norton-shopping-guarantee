<?php
/**
 * Norton Shopping Guarantee with Package Protection
 *
 * @package               norton-shopping-guarantee
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = array(
	'nsgpp_sn',
	'nsgpp_plugin_activation_register',
);
foreach ( $options as $option ) {
	if ( get_option( $option ) ) {
		delete_option( $option );
	}
}
