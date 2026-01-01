<?php
/**
 * SellApp Payments Blocks integration
 *
 * @package SellApp/Blocks
 * @since 1.0.0
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * SellApp Payments Blocks integration
 *
 * @since 1.0.0
 */
final class SELLAPP_WC_Gateway_Blocks_Support extends AbstractPaymentMethodType {
	/**
	 * The gateway instance.
	 *
	 * @var SELLAPP_WC_Gateway
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'sellapp';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option('woocommerce_sellapp_settings', []);
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[$this->name];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/assets/js/frontend/blocks.js';
		$script_asset_path = SELLAPP_PLUGIN_BASE_PATH . 'assets/js/frontend/blocks.asset.php';
		$script_asset      = file_exists($script_asset_path)
			? require($script_asset_path)
			: array(
				'dependencies' => array(),
				'version'      => SELLAPP_PLUGIN_VERSION
			);
		$script_url        = SELLAPP_PLUGIN_BASE_URL . $script_path;

		wp_register_script(
			'sellapp-wc-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if (function_exists('wp_set_script_translations')) {
			wp_set_script_translations('sellapp-wc-blocks', 'sellapp', SELLAPP_PLUGIN_BASE_PATH . 'languages/');
		}

		return ['sellapp-wc-blocks'];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'sellapp_data' => [
				'title'       => $this->get_setting('title'),
				'description' => $this->get_setting('description'),
				'supports'    => array_filter($this->gateway->supports, [$this->gateway, 'supports']),
			],
			'logo_url'    => SELLAPP_PLUGIN_BASE_URL . 'assets/images/logo.png',
		];
	}
}