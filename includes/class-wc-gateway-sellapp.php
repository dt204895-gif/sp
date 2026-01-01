<?php
/**
 * WC_Gateway_SellApp class
 *
 * @author   SellApp
 * @package  WooCommerce SellApp Payments Gateway
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SellApp Gateway.
 *
 * @class    SELLAPP_WC_Gateway
 * @version  1.0.0
 */
class SELLAPP_WC_Gateway extends WC_Payment_Gateway {

	/**
	 * API key for SellApp service.
	 * @var string
	 */
	public $api_key;

	/**
	 * Debug mode.
	 * @var string
	 */
	public $debug_mode;

	/**
	 * Charge description.
	 * @var string
	 */
	public $charge_description;

	/**
	 * X-STORE header value.
	 * @var string
	 */
	public $x_store;

	/**
	 * Webhook URL.
	 * @var string
	 */
	public $webhook_url;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'sellapp';
		$this->icon               = apply_filters('sellapp_woocommerce_icon', SELLAPP_PLUGIN_BASE_URL . 'assets/images/paypal.svg');
		$this->has_fields         = true;
		$this->method_title       = __('SellApp', 'sellapp');
		$this->method_description = __('Get paid in crypto, PayPal, and many other payment methods', 'sellapp');
		$this->webhook_url        = add_query_arg('wc-api', 'sellapp_webhook', home_url('/'));
		
		$this->supports = array(
			'products',
			'refunds'
		);

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title          = $this->get_option('title');
		$this->description    = $this->get_option('description');
		$this->debug_mode     = $this->get_option('debug_mode');
		$this->api_key        = $this->get_option('api_key');
		$this->charge_description = $this->get_option('charge_description');
		$this->x_store        = $this->get_option('x_store');

		// Actions
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_api_sellapp_webhook', array($this, 'webhook_handler'));
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __('Enable Payment Gateway', 'sellapp'),
				'type'    => 'checkbox',
				'label'   => __('Enable SellApp', 'sellapp'),
				'default' => 'yes'
			),
			'debug_mode' => array(
				'title'   => __('Debug Mode', 'sellapp'),
				'type'    => 'checkbox',
				'label'   => __('Enable Debug Mode', 'sellapp'),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __('Title', 'sellapp'),
				'type'        => 'text',
				'description' => __('Configure the gateway title shown during checkout.', 'sellapp'),
				'default'     => __('SellApp', 'sellapp'),
			),
			'description' => array(
				'title'       => __('Description', 'sellapp'),
				'type'        => 'textarea',
				'description' => __('Configure the gateway description shown during checkout.', 'sellapp'),
				'default'     => __('Pay with crypto, PayPal, and many other payment methods using sell.app', 'sellapp')
			),
			'api_key' => array(
				'title'       => __('SellApp API Key', 'sellapp'),
				'type'        => 'text',
				'description' => __('Enter your SellApp API Key, found in your store settings', 'sellapp'),
				'default'     => '',
			),
			'charge_description' => array(
				'title'       => __('Charge Description', 'sellapp'),
				'type'        => 'text',
				'description' => __('The description of the charge. A description of "Charge #" and a WooCommerce order ID of "123" will result in "Charge #123"', 'sellapp'),
				'default'     => 'Charge #',
			),
			'x_store' => array(
				'title'       => __('X-STORE', 'sellapp'),
				'type'        => 'text',
				'description' => __('Optionally pass your store slug using this parameter. Not required unless you have more than one store. Will be added in the X-STORE header for each request.', 'sellapp') . ' ' .
                                 __('Example: You have two SellApp stores, with a slug of "bob" and "john". You would like to use the "john" store to process WooCommerce payments. Inserting "john" here passes the X-STORE header with value "john" to the SellApp API.', 'sellapp'),
				'default'     => '',
			),
		);
	}

	/**
	 * Create payment for an order
	 *
	 * @param WC_Order $order
	 * @return string URL to redirect to
	 * @throws Exception
	 */
	public function create_payment($order) {
		// Create customer data array with non-empty values
		$customer_data = array();
		
		$first_name = $order->get_billing_first_name();
		$last_name = $order->get_billing_last_name();
		if (!empty($first_name) || !empty($last_name)) {
			$customer_data['name'] = trim($first_name . ' ' . $last_name);
		}
		
		$email = $order->get_billing_email();
		if (!empty($email)) {
			$customer_data['email'] = $email;
		}
		
		$phone = $order->get_billing_phone();
		if (!empty($phone)) {
			$customer_data['phone'] = $phone;
		}
		
		$country = $order->get_billing_country();
		if (!empty($country)) {
			$customer_data['country'] = $country;
		}
		
		$state = $order->get_billing_state();
		if (!empty($state)) {
			$customer_data['state'] = $state;
		}
		
		$customer_id = $order->get_customer_id();
		if (!empty($customer_id)) {
			$customer_data['customer_id'] = $customer_id;
		}
		
		$customer_data['order_id'] = $order->get_id();
		$customer_data['store_url'] = get_site_url();

		$params = array(
			'reference' => $this->charge_description . $order->get_id(),
			'currency' => $order->get_currency(),
			'total' => (float)$order->get_total() * 100,
			'use_all_payment_methods' => true,
			'return_url' => $this->get_return_url($order),
			'webhook' => add_query_arg('wc_id', $order->get_id(), $this->webhook_url),
			'email' => $order->get_billing_email(),
			'metadata' => array(
				'origin' => 'WOOCOMMERCE',
				'platform' => 'WordPress ' . get_bloginfo('version'),
				'plugin_version' => SELLAPP_PLUGIN_VERSION
			)
		);
		
		// Only add customer_data if we have data
		if (!empty($customer_data)) {
			$params['customer_data'] = $customer_data;
		}

		$route = "/v2/charges";
		
		// Log the request parameters for debugging
		if ($this->debug_mode == 'yes') {
			$this->log('Payment request to: ' . $route);
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r.Found
			$this->log('Payment request parameters: ' . print_r($params, true));
		}

		$response = $this->create_charge($route, $params);

		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			$error_code = $response->get_error_code();

			if ($this->debug_mode == 'yes') {
				$this->log('API Error: ' . $error_code . ' - ' . $error_message);
			}

			$errorMessage = __('Payment error: ', 'sellapp') . $error_message;
			throw new Exception(esc_html($errorMessage));
		} else if (isset($response['body']) && !empty($response['body'])) {
			$responseDecode = json_decode($response['body'], true);
			if (isset($responseDecode['error']) && !empty($responseDecode['error'])) {
				$errorMessage = __('Payment Gateway Error: ', 'sellapp') . $responseDecode['status'].'-'.$responseDecode['error']; 
				throw new Exception(esc_html($errorMessage));
			}
			
			$url = $responseDecode['data']['url'];

			return $url;
		} else {
			$errorMessage = __('Payment Gateway Error: Empty response received.', 'sellapp');
			throw new Exception(esc_html($errorMessage));
		}
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment($order_id) {
		$order = wc_get_order($order_id);
		try {
			$payment = $this->create_payment($order);

			if ($this->debug_mode == 'yes') {
				$this->log('Charge request for order ' .$order_id . ' returned: ' . $payment);
			}
			
			if ($payment) {
				return array(
					'result' => 'success',
					'redirect' => $payment
				);
			} else {
				$errorMessage = __('Payment Gateway Error: Empty response received.', 'sellapp');
				throw new Exception(esc_html($errorMessage));
			}
		} catch (Exception $e) {
            $message = $e->getMessage();
			if ($this->debug_mode == 'yes') {
				$this->log($message);
			}
			
            WC()->session->set('refresh_totals', true);
            wc_add_notice($message, 'error');
            return array(
                'result' => 'failure',
                'redirect' => wc_get_checkout_url(),
				'message' => $message,
            );
        }
	}

	/**
	 * Handle webhooks from SellApp
	 */
	public function webhook_handler() {
		global $woocommerce;

		// Verify nonce first
		if (isset($_GET['sellappnonce']) && !wp_verify_nonce(sanitize_key($_GET['sellappnonce']), 'sellapp-web_hook_handler')) {
			status_header(401); // Use appropriate HTTP status code
			$this->log('Error: Invalid nonce received.');
			exit('Invalid nonce');
		}

		// Read and decode the input
		$raw_input = file_get_contents('php://input');
		$decoded_data = json_decode($raw_input, true);

		// Validate JSON structure
		if (!is_array($decoded_data) || !isset($decoded_data['data'])) {
			status_header(400); // Bad Request
			$this->log('Error: Invalid JSON or missing "data" key in webhook payload. Payload: ' . $raw_input);
			exit('Invalid payload');
		}

		if ($this->debug_mode == 'yes') {
			// Log the raw data before sanitization for debugging comparison if needed
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r.Found
			$this->log('Webhook Handler received raw data: ' . print_r($decoded_data, true));
		}

		// --- Sanitize Input Data ---
		$sanitized_charge_id   = isset($decoded_data['data']['id']) ? sanitize_text_field($decoded_data['data']['id']) : null;
		$sanitized_reference   = isset($decoded_data['data']['reference']) ? sanitize_text_field($decoded_data['data']['reference']) : null;
		$sanitized_status      = isset($decoded_data['data']['status']) ? sanitize_key($decoded_data['data']['status']) : null; // Sanitize status from payload if needed for initial checks/logging, but prefer API response.

		// Sanitize potential WC Order ID from request params
		$wc_id_from_request = isset($_REQUEST['wc_id']) ? absint(wp_unslash($_REQUEST['wc_id'])) : 0;

		// --- Use Sanitized Data ---

		if (empty($sanitized_charge_id)) {
			status_header(400);
			$this->log('Error: Invalid webhook data - missing or empty charge ID after sanitization.');
			exit('Missing charge ID');
		}

		// ALWAYS verify the webhook data with a server-to-server API call using the sanitized charge ID
		$sellapp_order = $this->valid_sellapp_order($sanitized_charge_id);

		if (!$sellapp_order) {
			// Log the sanitized ID used for the failed lookup
			$this->log('Error: Could not verify charge using sanitized ID #' . $sanitized_charge_id . ' with the API.');
			status_header(400); // Or potentially 404 if the charge truly doesn't exist
			exit('Could not verify charge');
		}

		// Trust the data from the API call ($sellapp_order) as the source of truth from here on.
		$api_status = isset($sellapp_order['status']) ? sanitize_key($sellapp_order['status']) : null; // Sanitize status from API response just in case

		if ($this->debug_mode == 'yes') {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r.Found
			$this->log('SellApp API verified charge data: ' . print_r($sellapp_order, true));
		}

		// Determine WooCommerce Order ID
		$order_id_to_process = 0;
		if ($wc_id_from_request > 0) {
			$order_id_to_process = $wc_id_from_request;
		} elseif (!empty($sanitized_reference)) {
			// Try to extract order ID from sanitized reference as a fallback
			$description = $this->charge_description; // Assuming this is sanitized or safe
			if (strpos($sanitized_reference, $description) === 0) {
				$potential_id = substr($sanitized_reference, strlen($description));
				$order_id_to_process = absint($potential_id); // Ensure it's an integer
			}
		}

		if ($order_id_to_process === 0) {
			status_header(400);
			$this->log('Error: Could not determine WooCommerce order ID from request or reference. Request wc_id: ' . $wc_id_from_request . ', Sanitized Reference: ' . $sanitized_reference);
			exit('Could not determine Order ID');
		}

		$order = wc_get_order($order_id_to_process);

		if (!$order) {
			status_header(404); // Not Found
			$this->log('Error: WooCommerce order #' . $order_id_to_process . ' not found.');
			exit('Order not found');
		}

		if ($this->debug_mode == 'yes') {
			$this->log('Processing WooCommerce order: ' . $order->get_id());
		}

		// Use the sanitized ID and the API status for logging
		$this->log('Processing Order #' . $order_id_to_process . ' for SellApp Charge #' . $sanitized_charge_id . '. API Status: ' . $api_status);

		// Process the verified status from the API response
		switch ($api_status) {
			case 'COMPLETED':
				$this->complete_order($order_id_to_process); // Assuming complete_order takes order ID
				break;
			case 'PENDING':
				$order->update_status('on-hold', sprintf(__('Payment is pending verification (SellApp Charge: %s)', 'sellapp'), $sanitized_charge_id));
				break;
			case 'WAITING_FOR_CONFIRMATIONS':
				$order->update_status('on-hold', sprintf(__('Awaiting crypto currency confirmations (SellApp Charge: %s)', 'sellapp'), $sanitized_charge_id));
				break;
			case 'PARTIAL':
				$order->update_status('on-hold', sprintf(__('Cryptocurrency payment only partially paid (SellApp Charge: %s)', 'sellapp'), $sanitized_charge_id));
				break;
			case 'VOIDED':
				$order->update_status('failed', sprintf(__('Payment has been voided (SellApp Charge: %s)', 'sellapp'), $sanitized_charge_id));
				break;
			case 'CANCELED':
			case 'CANCELLED':
				$order->update_status('cancelled', sprintf(__('Payment has been cancelled (SellApp Charge: %s)', 'sellapp'), $sanitized_charge_id));
				break;
			case 'REFUNDED':
				$order->add_order_note(sprintf(__('Payment has been refunded via SellApp (Charge: %s)', 'sellapp'), $sanitized_charge_id));;
				break;
			default:
				// Log unknown status from API
				$this->log('Notice: Received unknown status "' . $api_status . '" from SellApp API for Charge #' . $sanitized_charge_id . ' and Order #' . $order_id_to_process);
		}

		// Send a 200 OK response to acknowledge receipt of the webhook
		status_header(200);
		exit;
	}
			
	/**
	 * Make authenticated requests to SellApp API
	 *
	 * @param string $route API route/endpoint
	 * @param mixed $body Request body (array will be converted to JSON)
	 * @param array $extra_headers Any extra headers to include
	 * @param string $method HTTP method (GET, POST, etc)
	 * @return array|WP_Error Response or error
	 */
	function create_charge($route, $body = false, $extra_headers = false, $method = "POST") {
		$server = 'https://sell.app/api';
		$url = $server . $route;

		if ($this->debug_mode == 'yes') {
			$this->log('Making ' . $method . ' request to: ' . $url);
			if ($body) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r.Found
				$this->log('Request body: ' . print_r($body, true));
			}
		}

		$uaString = 'SellApp WooCommerce (PHP ' . PHP_VERSION . ')';
		$apiKey = $this->api_key;
		$headers = array(
			'Content-Type'  => 'application/json',
			'Accept' => 'application/json',
			'User-Agent' => $uaString,
			'Authorization' => 'Bearer ' . $apiKey,
		);

		if (!empty($this->x_store)) {
			$headers['X-STORE'] = sanitize_text_field($this->x_store);
		}

		if ($extra_headers && is_array($extra_headers)) {
			$headers = array_merge($headers, $extra_headers);
		}

		$options = array(
			'method'  => $method,
			'timeout' => 30,
			'headers' => $headers,
		);

		if (!empty($body)) {
			$options['body'] = wp_json_encode($body);
		}

		$response = wp_safe_remote_post ($url, $options);

		// Enhanced debugging for response
		if ($this->debug_mode == 'yes') {
			if (is_wp_error($response)) {
				$this->log('API Error Response: ' . $response->get_error_message());
			} else {
				$code = wp_remote_retrieve_response_code($response);
				$body = wp_remote_retrieve_body($response);
				$this->log('API Response Code: ' . $code);
				$this->log('API Response Body: ' . $body);
			}
		}

		return $response;
	}
	
	/**
	 * Validate a SellApp order/charge
	 *
	 * @param int $charge_id Charge ID to validate
	 * @return array|null Order data or null if invalid
	 */
	public function valid_sellapp_order($charge_id) {
		$route = "/v2/charges/" . $charge_id;
		$response = $this->create_charge($route, '', '', 'GET');

		if ($this->debug_mode == 'yes') {
			$this->log('Order validation returned: ' . wp_remote_retrieve_body($response));
		}

		if (is_wp_error($response)) {
			mail(get_option('admin_email'), __('Unable to verify order via SellApp API', 'sellapp'), $charge_id);
			return null;
		} elseif (isset($response['response']['code']) && $response['response']['code'] == 200) {
			$responseDecode = json_decode(wp_remote_retrieve_body($response), true);
			// Return the data directly, as that's where the order info is
			return $responseDecode['data'];
		}
		
		return null;
	}
	
	/**
	 * Complete a WooCommerce order
	 * 
	 * @param int $wc_id WooCommerce order ID
	 */
	public function complete_order($wc_id) {
		$order = wc_get_order($wc_id);
		$order->payment_complete();
	}

	/**
	 * Check if an option exists
	 * 
	 * @param string $option_name Option name
	 * @return mixed Option value or false
	 */
	public function option_exists($option_name) {
		$value = get_option($option_name);
		return $value;
	}

	/**
	 * Log messages if debug mode is enabled
	 * 
	 * @param mixed $content Content to log
	 */
	public function log($content) {
		$debug = $this->debug_mode;
		if ($debug == 'yes') {
			global $wp_filesystem;

			// Initialize the WP filesystem, no more direct PHP file functions.
			if (empty($wp_filesystem)) {
				require_once (ABSPATH . '/wp-admin/includes/file.php');
				WP_Filesystem();
			}

			// Check if WP_Filesystem is initialized correctly.
			if (!$wp_filesystem) {
				// Optionally, log an error about filesystem initialization failure, maybe using error_log?
				// error_log("SellApp Log Error: WP_Filesystem could not be initialized.");
				return; // Cannot proceed without filesystem
			}

			$option_name = 'sellapp_logfile_prefix';
			$logfile_prefix = get_option($option_name);

			if (!$logfile_prefix) {
				$logfile_prefix = md5(uniqid(wp_rand(), true));
				update_option($option_name, $logfile_prefix);
			}

			// Ensure WC_LOG_DIR is defined, otherwise fallback to uploads dir
			if (defined('WC_LOG_DIR')) {
				$log_dir = WC_LOG_DIR;
			} else {
				$upload_dir = wp_upload_dir();
				$log_dir = $upload_dir['basedir'] . '/wc-logs/';
				// Ensure the directory exists
				if (!$wp_filesystem->is_dir($log_dir)) {
					$wp_filesystem->mkdir($log_dir);
				}
			}

			$filename = $logfile_prefix . '_sellapp_debug.log';
			$file_path = trailingslashit($log_dir) . $filename;

			try {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r.Found
				$log_entry = "\n" . gmdate("Y-m-d H:i:s") . ": " . print_r($content, true);
				
				// Append content to the log file using WP_Filesystem
				if (!$wp_filesystem->put_contents($file_path, $log_entry, FS_CHMOD_FILE | FILE_APPEND)) {
					// Log failure to write? Be careful not to cause infinite loop if logging itself fails.
					// error_log("SellApp Log Error: Failed to write to log file: " . $file_path);
				}
			} catch (Exception $e) {
				 // Log exception? Again, avoid infinite loops.
				// error_log("SellApp Log Exception: " . $e->getMessage());
			}
		}
	}
}