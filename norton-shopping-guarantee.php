<?php
/**
 * Norton Shopping Guarantee with Package Protection
 *
 * @package               norton-shopping-guarantee
 *
 * Plugin Name:           Norton Shopping Guarantee with Package Protection
 * Plugin URI:            https://norton.buysafe.com/for-merchants/
 * Description:           Increase sales by providing a unique and holistic buyer protection program, offering both a shopping guarantee and shipping insurance, utilizing trust badges to guide potential customers towards a confident purchase from your online store.
 * Version:               1.0.0
 * Author:                Norton Shopping Guarantee
 * Author URI:            https://norton.buysafe.com/
 * Text Domain:           norton-shopping-guarantee
 * Domain Path:           /languages
 * Requires PHP:          7.2
 * Requires at least:     4.0
 * Tested up to:          6.5
 * WC tested up to:       8.5
 * WC requires at least:  8.0
 * Woo:                   18734003837994:ea8d993eba66aec921bfb91b7248ef9f
 * License:               GPL-2.0+
 * License URI:           http://www.gnu.org/licenses/gpl-2.0.txt
 */

// define( 'WP_DEBUG', true );                        // WP_DEBUG.
// define( 'WP_DEBUG_DISPLAY', true );                // WP_DEBUG_DISPLAY.
// define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );  // WP_DISABLE_FATAL_ERROR_HANDLER.
// define( 'WP_DEBUG_LOG', true );                    // WP_DEBUG_LOG.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Test to see if WooCommerce is active (including network activated).
$nsgpp_plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';
if ( ! in_array( $nsgpp_plugin_path, wp_get_active_and_valid_plugins() ) ) {
	if ( 'plugins.php' == $pagenow ) {
		add_action(
			'plugins_loaded',
			function () {
				add_action( 'admin_notices', 'nsgpp_woo_missing' );
			}
		);
	}
	return; // Exit from the app if WooCommerce isn't enabled.
}

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			// Declare compatibility.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );  // Cart and Checkout blocks.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );   // HPOS (High-performance order storage).
		}
	}
);

/**
 * Deactivation handler
 */
function nspgg_plugin_deactivate() {
}
register_deactivation_hook( __FILE__, 'nspgg_plugin_deactivate' );

/**
 * Avtivation handler
 */
function nspgg_plugin_activate() {
	add_option( 'nsgpp_plugin_activation_register', true );
}
register_activation_hook( __FILE__, 'nspgg_plugin_activate' );

/**
 * Register our plugin on admin init
 */
function nsgpp_plugin_register() {
	if ( get_option( 'nsgpp_plugin_activation_register', false ) ) {
		delete_option( 'nsgpp_plugin_activation_register' );
		add_action( 'admin_notices', 'nsgpp_activation_notice' );
	}

	if ( ! get_option( 'nsgpp_sn' ) ) {
		nsgpp_update_store_data();
	}
}
add_action( 'admin_init', 'nsgpp_plugin_register' );

/**
 * Returns url for linking to the registration / dashboard
 *
 * @return string
 */
function nsgpp_get_reg_url() {
	$current_user = wp_get_current_user();
	$current_user_id = $current_user->ID;
	$action_url = 'https://my.NortonShoppingGuarantee.com/Registration';
	return $action_url . '/MerchantThirdParty/?pc=NortonSelfServiceWooAppSI&user_id=' . $current_user_id
			. '&host_name=' . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' );
}

/**
 * Returns url for linking to the registration / dashboard
 *
 * @return string
 */
function nsgpp_get_register_url() {
	$endpoint = 'https://my.NortonShoppingGuarantee.com/Registration/MerchantThirdParty/';
	$pc = 'NortonSelfServiceWooAppSI';
	$current_user = wp_get_current_user();
	$current_user_id = $current_user->ID;
	$current_user_email = $current_user->user_email;
	$current_user_first_name = $current_user->first_name;
	$current_user_last_name = $current_user->last_name;
	$store_raw_country = get_option( 'woocommerce_default_country' );
	$split_country = explode( ':', $store_raw_country );
	$store_country = $split_country[0];
	$store_state   = $split_country[1];

	$params = array(
		'pc' => $pc,
		'host_name' => isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '',
		'site_url' => site_url(),
		'home_url' => home_url(),
		'store_name' => get_bloginfo( 'name' ),
		'store_email' => get_bloginfo( 'admin_email' ),
		'user_id' => $current_user_id,
		'user_email' => $current_user_email,
		'user_first_name' => $current_user_first_name,
		'user_last_name' => $current_user_last_name,
		'store_city' => get_option( 'woocommerce_store_city' ),
		'store_postcode' => get_option( 'woocommerce_store_postcode' ),
		'store_state' => $store_state,
		'store_country' => $store_country,
		'store_currency' => get_option( 'woocommerce_currency' ),
		'address' => get_option( 'woocommerce_store_address' ),
		'address2' => get_option( 'woocommerce_store_address_2' ),
	);
	$query_string = http_build_query( $params );

	$url = $endpoint . '?' . $query_string;

	return $url;
}

/**
 * Returns url for linking to the registration / dashboard
 *
 * @return string
 */
function nsgpp_get_accountsetup_url() {
	return ( get_option( 'nsgpp_sn' ) ) ? nsgpp_get_reg_url() : nsgpp_get_register_url();
}

  /**
   * Display error if woocommerce is disabled
   */
function nsgpp_woo_missing() {
	echo '<div class="error"><p>Norton Shopping Guarantee requires WooCommerce to be active.</p></div>';
}

/**
 * Plugin activation handler to display notice
 */
function nsgpp_activation_notice() {
	echo '<div class="notice notice-success"><p> To enable Norton Shopping Guarantee on your website, please <strong> <a href="' . esc_url( nsgpp_get_accountsetup_url() ) . '" target="_blank">connect your store</a></strong>. We\'ll create a new account if you don\'t have one already. </p></div>';
}

/**
 * Add links to the meta links list
 *
 * @param array  $links        An array of the plugin's metadata.
 * @param string $plugin_file  Path to the plugin file.
 * @return array
 */
function nsgpp_add_meta_links( $links, $plugin_file ) {
	if ( strpos( $plugin_file, basename( __FILE__ ) ) ) {
		$links[] = '<a href="' . esc_url( nsgpp_get_accountsetup_url() ) . '" target="_blank" style="font-weight:700;">NSG Account Settings</a>';
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'nsgpp_add_meta_links', 10, 4 );

/**
 * Get Store Number
 */
function nsgpp_update_store_data() {
	$api_url = 'https://my.NortonShoppingGuarantee.com/Registration/MerchantThirdParty/GetStoreData?host=' . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' );
	$response = wp_remote_get(
		$api_url,
		array(
			'method' => 'GET',
			'headers' => array( 'Content-Type' => 'application/json' ),
		)
	);
	$res_body = json_decode( wp_remote_retrieve_body( $response ) );
	if ( isset( $res_body->StoreNumber ) && $res_body->StoreNumber ) {  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase --- StoreNumber is from our external API, and cannot be changed.
		add_option( 'nsgpp_sn', $res_body->StoreNumber );                 // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase --- StoreNumber is from our external API, and cannot be changed.
	}
}

/**
 * Set WC session var on kicker update
 */
function nsgpp_kicker_update_action() {
	if ( isset( $_POST['IsEPSI'] ) && isset( $_POST['nsgpp_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nsgpp_nonce'] ) ), 'nsgpp_nonce' ) ) {
		WC()->session->set( 'IsEPSI', ( sanitize_text_field( wp_unslash( $_POST['IsEPSI'] ) ) ? '1' : '0' ) );
		echo wp_json_encode(
			array(
				'IsEPSI'      => WC()->session->get( 'IsEPSI' ),
				'cart_fee'    => number_format( nsgpp_calculate_cart_fee(), 2 ),
			)
		);
	}
	die();
}
add_action( 'wp_ajax_nsgppkicker_update_action', 'nsgpp_kicker_update_action' );
add_action( 'wp_ajax_nopriv_nsgppkicker_update_action', 'nsgpp_kicker_update_action' );

/**
 * Retrieve the WC session data
 */
function nsgpp_get_kicker_state_action() {
	echo wp_json_encode(
		array(
			'IsEPSI'      => WC()->session->get( 'IsEPSI' ),
			'cart_fee'    => number_format( nsgpp_calculate_cart_fee(), 2 ),
		)
	);
	die();
}
add_action( 'wp_ajax_nsgppkicker_state_action', 'nsgpp_get_kicker_state_action' );
add_action( 'wp_ajax_nopriv_nsgppkicker_state_action', 'nsgpp_get_kicker_state_action' );

/**
 * Calculate the cart fee and enforce min/max
 */
function nsgpp_calculate_cart_fee() {
	$percentage = 0.02;
	$cart_total = min( WC()->cart->get_cart_contents_total(), 5000 );   // enforce maximum cart value of $5000.
	$cart_fee = round( $cart_total * $percentage, 2 );
	return max( $cart_fee, 0.98 );  // enforce minimum 0.98.
}

/**
 * Add the NSG fee to the current shopping cart
 *
 * @param WC_Cart $cart The current shopping cart as supplied by woocommerce_cart_calculate_fees.
 */
function nsgpp_add_pp_fees( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	if ( WC()->session->get( 'IsEPSI' ) ) {
		$cart_fee = nsgpp_calculate_cart_fee();
		WC()->cart->add_fee( 'NSG Package Protection ', $cart_fee );
	}
}
add_action( 'woocommerce_cart_calculate_fees', 'nsgpp_add_pp_fees' );

add_action(
	'wp_enqueue_scripts',
	function () {
		$sn = get_option( 'nsgpp_sn' );
		$gjs_code = 'https://guarantee-cdn.com/SealCore/api/gjs?t=Msp197&SN=' . $sn;
		if ( $sn ) {
			wp_enqueue_script( 'nsgpp_script_gjs', $gjs_code, array(), null, true );
			wp_add_inline_script( 'nsgpp_script_gjs', 'const nsgpp_nonce = ' . wp_json_encode( wp_create_nonce( 'nsgpp_nonce' ) ), 'before' );
		}
	}
);
