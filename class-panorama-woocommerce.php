<?php
/**
 * Plugin Name: Panorama WooCommerce
 * Plugin URI:  https://www.projectpanorama.com/add-ons/psp-woocommerce
 * Description: Allow a user to purchase a WooCommerce product to duplicate a Project
 * Version:     1.1
 * Author:      SnapOrbital
 * Author URI:  https://www.snaporbital.com/
 * License:     GPL2
 * Text Domain: psp-woocommerce
 * Domain Path: /languages/
 */

define( 'PSP_WOO_VER', '1.1' );

/**
 * Class Panorama_WooCommerce
 */
class Panorama_WooCommerce {

	/**
	 * Panorama_WooCommerce constructor.
	 */
	function __construct() {

		// No panorama plugin, bail.
		if ( ! defined( 'PROJECT_PANORAMA_DIR' ) ) {
			return;
		}

		add_action( 'init', array( $this, 'register_panorama_project_product' ) );
	}

	/**
	 * Handles the `init` action
	 *
	 * Includes and inits the WC_Product_Panorama_Product Class
	 *
	 * @param int $post_id The WooCommerce Order.
	 * @return void
	 */
	function register_panorama_project_product() {

		include( plugin_dir_path( __FILE__ ) . 'includes/WC_Product_Panorama_Product.php' );

		WC_Product_Panorama_Product::init();
	}

}

add_action( 'plugins_loaded', 'panorama_woocommerce_init', 10001, 1 );
function panorama_woocommerce_init() {
	new Panorama_WooCommerce();
	load_plugin_textdomain( 'psp-woocommerce', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
