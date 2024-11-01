<?php
/**
 * Plugin Name:     ViaTim WooCommerce Extension
 * Plugin URI:      https://www.viatim.nl
 * Description:     Send your packages with ViaTim.
 * Author:          Roy Sijnesael (For ViaTim)
 * Version:         1.0.2
 * Text Domain: 	viatim-woocommerce-plugin
 * @package         ViaTim_WooCommerce_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	function ViaTim_WooCommerce_Shipping_init() {
		if ( ! class_exists( 'ViaTim_WooCommerce_Shipping' ) ) {
			class ViaTim_WooCommerce_Shipping extends WC_Shipping_Method {
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct() {
					$this->id                 = 'viatim-woocommerce-plugin'; // Id for your shipping method. Should be unique.
					$this->method_title       = __( 'ViaTim' );  // Title shown in admin
					$this->method_description = __( 'Send your packages with ViaTim.', 'viatim-woocommerce-plugin' ); // Description shown in admin
					$this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
					$this->init();

					if ( $this->is_woocommerce_activated() === false ) {
						add_action( 'admin_notices', array ( $this, 'need_woocommerce' ) );
						return;
					}

					// if ( $this->is_date_activated() === false ) {
					// 	add_action( 'admin_notices', array ( $this, 'need_date' ) );
					// 	return;
					// }
				}

				/**
				 * Init your settings
				 *
				 * @access public
				 * @return void
				 */
				function init() {
					// Load the settings API
					$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
					$this->init_settings(); // This is part of the settings API. Loads settings you previously init.
					$this->translations();

					// Define user set variables
					$this->enabled					= isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : $this->enabled;
					$this->api_key    				= isset( $this->settings['api_key '] ) ? $this->settings['api_key'] : '';
					$this->selected_options			= isset( $this->settings['selected_options '] ) ? $this->settings['selected_options '] : '';
					$this->same_day_delivery    	= isset( $this->settings['same_day_delivery '] ) ? $this->settings['same_day_delivery '] : '';
					$this->next_day_delivery    	= isset( $this->settings['next_day_delivery '] ) ? $this->settings['next_day_delivery '] : '';
					// $this->international_delivery 	= isset( $this->settings['international_delivery '] ) ? $this->settings['international_delivery '] : '';
					$this->free_delivery  			= isset( $this->settings['free_delivery '] ) ? $this->settings['free_delivery '] : '';
					$this->same_day_start  			= isset( $this->settings['same_day_start '] ) ? $this->settings['same_day_start '] : '';
					$this->same_day_end  			= isset( $this->settings['same_day_end '] ) ? $this->settings['same_day_end '] : '';
					
					if(is_wc_endpoint_url( 'order-received' )) {
						include('includes/order.php');
						getOrder($this->settings['api_key'], $this->settings['enabled']);	
					}

					// Save settings in admin if you have any defined
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
				}

				function translations() {
					$locale = apply_filters( 'plugin_locale', get_locale(), 'viatim-woocommerce-plugin' );
					$dir    = trailingslashit( WP_LANG_DIR );
			
					load_textdomain( 'viatim-woocommerce-plugin', $dir . 'viatim-woocommerce-plugin/viatim-woocommerce-plugin_' . $locale . '.mo' );
					load_textdomain( 'viatim-woocommerce-plugin', $dir . 'plugins/viatim-woocommerce-plugin_' . $locale . '.mo' );
					load_plugin_textdomain( 'viatim-woocommerce-plugin', false, dirname( plugin_basename(__FILE__) ) . '/languages' );
				}

				public function need_woocommerce() {
					$error = sprintf( __( 'ViaTim WooCommerce Plugin requires %sWooCommerce%s to be installed & activated!', 'viatim-woocommerce-plugin' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>' );
					$message = '<div class="error"><p>' . $error . '</p></div>';
					echo $message;
				}

				// public function need_date() {
				// 	$error = sprintf( __( 'ViaTim WooCommerce Plugin requires the <a href="https://wordpress.org/plugins/order-delivery-date-for-woocommerce/" target="_blank">Order Delivery Date for WooCommerce</a> plugin to be installed & activated!', 'viatim-woocommerce-plugin' ), '<a href="https://wordpress.org/plugins/order-delivery-date-for-woocommerce/" target="_blank">', '</a>' );
				// 	$message = '<div class="error"><p>' . $error . '</p></div>';
				// 	echo $message;
				// }

				/**
				* Check if woocommerce is activated
				*/
				public function is_woocommerce_activated() {
					$blog_plugins = get_option( 'active_plugins', array() );
					$site_plugins = get_site_option( 'active_sitewide_plugins', array() );

					if ( in_array( 'woocommerce/woocommerce.php', $blog_plugins ) || isset( $site_plugins['woocommerce/woocommerce.php'] ) ) {
						return true;
					} else {
						return false;
					}
				}

				/**
				* Check if delivery date plugin is activated
				*/
				public function is_date_activated() {
					$blog_plugins = get_option( 'active_plugins', array() );
					$site_plugins = get_site_option( 'active_sitewide_plugins', array() );

					if ( in_array( 'order-delivery-date-for-woocommerce/order_delivery_date.php', $blog_plugins ) || isset( $site_plugins['order-delivery-date-for-woocommerce/order_delivery_date.php'] ) ) {
						return true;
					} else {
						return false;
					}
				}

				/**
				 * calculate_shipping function.
				 *
				 * @access public
				 * @param mixed $package
				 * @return void
				 */
				public function calculate_shipping($package = array()) {
					include('includes/postcodes.php');
					include('includes/rates.php');

					checkPostcode($this->settings['api_key']);

					$date = new DateTime(null, new DateTimeZone('Europe/Amsterdam'));
					$day = date("l");
					$sameDayAvalible = false;

					// Checks if time is between X and X, if so, users can chose the Same Day Delivery option
					if($day != 'Sunday') {
						if($date->format('H:i') > $this->settings['same_day_start'] && $date->format('H:i') < $this->settings['same_day_end']) { //Times will be determined by User Input
							$sameDayAvalible = true;
						}
					}

					if($GLOBALS['postcodes']) { // Checks if the postcode is within ViaTim delivery Range
						if($country == "NL") {
							// Register the rate(s)
							if(in_array('Same Day Delivery', $this->settings['selected_options']) && $sameDayAvalible) { //Checks if Same Day Delivery is avalible
								$this->add_rate($sameDay);
							}

							if(in_array('Free Delivery', $this->settings['selected_options']) && $total > $this->settings['free_delivery']) {
								$this->add_rate($freeDelivery);
							} 

							else if(in_array('Next Day Delivery', $this->settings['selected_options'])) {
								$this->add_rate($nextDay);
							}
						}

						// else if(in_array('International Delivery', $this->settings['selected_options']) && $country !== "NL") {
						// 	$this->add_rate($international);
						// } 
					}
				}

				public function init_form_fields() {
					global $woocommerce;
					
					$this->form_fields  = array(
						'enabled'             => array(
							'title'           => __( 'ViaTim Delivery', 'viatim-woocommerce-plugin' ),
							'type'            => 'checkbox',
							'label'           => __( 'Activate', 'viatim-woocommerce-plugin' ),
							'default'         => 'no',
							'description'     => __('Activate the ViaTim delivery option for your webshop.', 'viatim-woocommerce-plugin'),
							'desc_tip'        => true
						),
						'api_key'   => array(
							'title'           => __( 'API Key', 'viatim-woocommerce-plugin' ),
							'type'            => 'text',
							'default'         => '',
							'description'     => __( "Entering your API key will make sure all orders from your webshop, send with ViaTim, will be created automatically in our <a href='#'>Control Panel</a>. <br> Don't have an API key yet? Contact us at <a href='mailto:it@viatim.nl'>it@viatim.nl</a>", 'viatim-woocommerce-plugin' ),
							'desc_tip'        => false
						),
						'selected_options'	=> array(
							'title'			  => __('Select Delivery Options', 'viatim-woocommerce-plugin' ),
							'type'			  => 'multiselect',
							'options'		  => array(
													'Next Day Delivery' => 'Next Day Delivery', 
													'Same Day Delivery' => 'Same Day Delivery',
													// 'International Delivery' => 'International Delivery',
													'Free Delivery' => 'Free Delivery'
												),
							'description'	  => __('Select which delivery options you want to use. Select multiple while holding the CTRL/CMD key.', 'viatim-woocommerce-plugin' ),
							'desc_tip'		  => false
						),
						'next_day_delivery'   => array(
							'title'           => __( 'Next Day Delivery', 'viatim-woocommerce-plugin' ),
							'type'            => 'text',
							'default'         => '0.00',
							'description'     => __( 'Enter the rates for Next Day Delivery.', 'viatim-woocommerce-plugin' ),
							'desc_tip'        => true
						),
						// 'international_delivery' => array(
						// 	'title'           => __( 'International Delivery', 'viatim-woocommerce-plugin' ),
						// 	'type'            => 'text',
						// 	'default'         => '0.00',
						// 	'description'     => __( 'Enter the rates for International Delivery.', 'viatim-woocommerce-plugin' ),
						// 	'desc_tip'        => true
						// ),
						'free_delivery'       => array(
							'title'           => __( 'Free Delivery', 'viatim-woocommerce-plugin' ),
							'type'            => 'text',
							'default'         => '0.00',
							'description'     => __( 'Enter the amount needed for Free Shipping.', 'viatim-woocommerce-plugin' ),
							'desc_tip'        => true
						),
						'same_day_delivery'   => array(
							'title'           => __( 'Same Day Delivery', 'viatim-woocommerce-plugin' ),
							'type'            => 'text',
							'default'         => '0.00',
							'description'     => __( 'Enter the rates for Same Day Delivery.', 'viatim-woocommerce-plugin' ),
							'desc_tip'        => true
						),
						'same_day_start'       => array(
							'title'           => __( 'Same Day Time Window (Start)', 'viatim-woocommerce-plugin' ),
							'type'            => 'time',
							'default'         => '06:00',
							'description'     => __( 'Enter the start time for Same Day Delivery.', 'viatim-woocommerce-plugin' ),
							'desc_tip'        => true
						),
						'same_day_end'       => array(
							'title'           => __( 'Same Day Time Window (End)', 'viatim-woocommerce-plugin' ),
							'type'            => 'time',
							'default'         => '11:00',
							'description'     => __( 'Enter the end time for Same Day Delivery.', 'viatim-woocommerce-plugin' ),
							'desc_tip'        => true
						),
					);
				}
			}
		}
	}

	add_action( 'woocommerce_shipping_init', 'ViaTim_WooCommerce_Shipping_init' );

	function viatim_shipping_method( $methods ) {
		$methods['ViaTim_WooCommerce_Shipping'] = 'ViaTim_WooCommerce_Shipping';
		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'viatim_shipping_method' );
}