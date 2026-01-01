<?php
/**
 * Plugin Name: SellApp
 * Description: Get paid in crypto, PayPal, and many other payment methods.
 * Version: 1.0.0
 * Requires at least: 4.9
 * Tested up to: 6.8
 * WC requires at least: 3.5
 * WC tested up to: 9.5
 * Author: Sell.app
 * Author URI: https://sell.app/
 * Text Domain: sellapp
 * License: MIT
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// --- Define Constants ---
// Define the plugin version.
define('SELLAPP_PLUGIN_VERSION', '1.0.0');

// Define the main plugin file path (useful for hooks like register_activation_hook).
define('SELLAPP_PLUGIN_FILE', __FILE__);

// Define the plugin's base directory path (with trailing slash).
// Use plugin_dir_path() for filesystem paths.
define('SELLAPP_PLUGIN_BASE_PATH', plugin_dir_path(__FILE__));

// Define the plugin's base URL (with trailing slash).
// Use plugin_dir_url() for web-accessible URLs.
define('SELLAPP_PLUGIN_BASE_URL', plugin_dir_url(__FILE__));

// Define the plugin basename (useful for filters like plugin_row_meta).
define('SELLAPP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Define the text domain for localization.
define('SELLAPP_TEXT_DOMAIN', 'sellapp');

/**
 * WC SellApp Payment gateway plugin class.
 *
 * @class SELLAPP_WC_Payments
 */
class SELLAPP_WC_Payments {

	/**
	 * Plugin bootstrapping.
	 */
	public static function init() {
		// Declare compatibility with custom order tables
		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_compatibility' ) );
		
		// Register activation hook using the defined constant
		register_activation_hook( SELLAPP_PLUGIN_FILE, array( __CLASS__, 'activate' ) );

		// SellApp Payments gateway class.
		add_action( 'plugins_loaded', array( __CLASS__, 'includes' ), 0 );

		// Make the SellApp Payments gateway available to WC.
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_gateway' ) );

		// Registers WooCommerce Blocks integration.
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'woocommerce_sellapp_blocks_support' ) );

		add_filter('plugin_row_meta', array(__CLASS__, 'row_meta_links'), 10, 4);
	}

	/**
	 * Declare compatibility with WooCommerce custom order tables.
	 */
	public static function declare_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		if ( ! self::check_woocommerce_is_active() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'The SellApp plugin requires the <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be active!', 'sellapp' ) );
		}
	}

	/**
	 * Check if WooCommerce is active.
	 * 
	 * @return bool
	 */
	public static function check_woocommerce_is_active() {
		// Check if WooCommerce is active via the standard plugins list
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			return true;
		}

		// Fallback check for multisite
		if ( is_multisite() ) {
			$plugins = get_site_option( 'active_sitewide_plugins' );
			if ( isset( $plugins['woocommerce/woocommerce.php'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add the SellApp Payment gateway to the list of available gateways.
	 *
	 * @param array $gateways
	 * @return array
	 */
	public static function add_gateway( $gateways ) {
		if ( ! in_array( 'SELLAPP_WC_Gateway', $gateways ) ) {
			$gateways[] = 'SELLAPP_WC_Gateway';
		}
		return $gateways;
	}

	/**
	 * Plugin includes.
	 */
	public static function includes() {
		if ( self::check_woocommerce_is_active() ) {
			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}
			
			require_once SELLAPP_PLUGIN_BASE_PATH . 'includes/class-wc-gateway-sellapp.php';
		}
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin absolute path.
	 *
	 * @return string
	 */
	public static function plugin_abspath() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 */
	public static function woocommerce_sellapp_blocks_support() {
		if ( class_exists( 'Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
			require_once SELLAPP_PLUGIN_BASE_PATH . 'includes/blocks/class-wc-sellapp-payments-blocks.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					// Ensure the class exists before trying to instantiate it.
					if ( class_exists( 'SELLAPP_WC_Gateway_Blocks_Support' ) ) {
						$payment_method_registry->register( new SELLAPP_WC_Gateway_Blocks_Support() );
					}
				}
			);
		}
	}

	/**
	* Show row meta on the plugin screen.
	*
	* @param array  $links Plugin Row Meta.
	* @param string $plugin_file_name Plugin Base file name. Used here instead of $plugin_data and $status.
	* @return array Modified links array.
	*/
	public static function row_meta_links($links, $plugin_file_name) {
		// Use the prefixed basename constant for comparison
		if ( SELLAPP_PLUGIN_BASENAME === $plugin_file_name ) {
			$docs_url = 'https://docs.sell.app/';
			$api_docs_url = 'https://developer.sell.app/';
			$community_url = 'https://sell.app/discord';

			// Add links to the end of the array
			$links[] = '<a href="' . esc_url($docs_url) . '" aria-label="' . 
				esc_attr__('View SellApp documentation', 'sellapp') . '" target="_blank">' . 
				esc_html__('Docs', 'sellapp') . '</a>';

			$links[] = '<a href="' . esc_url($api_docs_url) . '" aria-label="' . 
				esc_attr__('View SellApp API documentation', 'sellapp') . '" target="_blank">' . 
				esc_html__('API Docs', 'sellapp') . '</a>';

			$links[] = '<a href="' . esc_url($community_url) . '" aria-label="' . 
				esc_attr__('Get community help', 'sellapp') . '" target="_blank">' . 
				esc_html__('Community', 'sellapp') . '</a>';
		}

		return $links;
	}
}

SELLAPP_WC_Payments::init();