<?php
/**
 * Plugin Name: Panorama WooCommerce
 * Plugin URI:  https://www.projectpanorama.com/add-ons/psp-woocommerce
 * Description: Allow a user to purchase a WooCommerce product to duplicate a Project
 * Version:     1.3.1
 * Author:      SnapOrbital
 * Author URI:  https://www.snaporbital.com/
 * License:     GPL2
 * Text Domain: psp-woocommerce
 * Domain Path: /languages/
 */

define( 'PSP_WOO_VER', '1.3.1' );

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

	if( !class_exists('WooCommerce') ) {
		return;
	}

	new Panorama_WooCommerce();

	load_plugin_textdomain( 'psp-woocommerce', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );

}

add_filter( 'plugins_api', 'psp_woocommerce_plugin_info', 10, 3 );
add_filter( 'site_transient_update_plugins', 'psp_woocommerce_push_update' );
add_action( 'upgrader_process_complete', 'psp_woocommerce_after_update', 10, 2);

function psp_woocommerce_plugin_info( $res, $action, $args ){

	// do nothing if this is not about getting plugin information
	if( $action !== 'plugin_information' )
		return false;

	// do nothing if it is not our plugin
	if( 'psp_woocommerce' !== $args->slug )
		return $res;

	// trying to get from cache first, to disable cache comment 18,28,29,30,32
	if( false == $remote = get_transient( 'psp_upgrade_psp_woocommerce' ) ) {

		// info.json is the file with the actual plugin information on your server
		$remote = wp_remote_get( 'https://www.projectpanorama.com/info.json', array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/json'
			) )
		);

		if ( !is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && !empty( $remote['body'] ) ) {
			set_transient( 'psp_upgrade_psp_woocommerce', $remote, 43200 ); // 12 hours cache
		}

	}

	if( $remote ) {

		$remote = json_decode( $remote['body'] );
		$res = new stdClass();
		$res->name = $remote->name;
		$res->slug = 'psp_woocommerce';
		$res->version = $remote->version;
		$res->tested = $remote->tested;
		$res->requires = $remote->requires;
		$res->author = '<a href="https://www.snaporbital.com/">SnapOrbital</a>'; // I decided to write it directly in the plugin
		$res->author_profile = 'https://profiles.wordpress.org/3pointross'; // WordPress.org profile
		$res->download_link = $remote->download_url;
		$res->trunk = $remote->download_url;
		$res->last_updated = $remote->last_updated;
		$res->sections = array(
			'description' => $remote->sections->description, // description tab
			'installation' => $remote->sections->installation, // installation tab
			'changelog' => $remote->sections->changelog, // changelog tab
			// you can add your custom sections (tabs) here
		);

		// in case you want the screenshots tab, use the following HTML format for its content:
		// <ol><li><a href="IMG_URL" target="_blank"><img src="IMG_URL" alt="CAPTION" /></a><p>CAPTION</p></li></ol>
		if( !empty( $remote->sections->screenshots ) ) {
			$res->sections['screenshots'] = $remote->sections->screenshots;
		}

		$res->banners = array(
			'low' => 'https://www.projectpanorama.com/assets/addons/woocommerce/banner-772x250.jpg',
			'high' => 'https://www.projectpanorama.com/assets/addons/woocommerce/banner-1544x500.jpg'
		);
			return $res;

	}

	return false;

}

function psp_woocommerce_push_update( $transient ) {

	if ( empty($transient->checked ) ) {
		return $transient;
	}

	// trying to get from cache first, to disable cache comment 10,20,21,22,24
	if( false == $remote = get_transient( 'psp_upgrade_psp_woocommerce' ) ) {

		// info.json is the file with the actual plugin information on your server
		$remote = wp_remote_get( 'https://www.projectpanorama.com/info.json', array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/json'
			) )
		);

		if ( !is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && !empty( $remote['body'] ) ) {
			set_transient( 'psp_upgrade_psp_woocommerce', $remote, 43200 ); // 12 hours cache
		}

	}

	if( $remote && !is_wp_error($remote) ) {

		$remote = json_decode( $remote['body'] );

		// your installed plugin version should be on the line below! You can obtain it dynamically of course
		if( $remote && version_compare( PSP_WOO_VER, $remote->version, '<' ) && version_compare($remote->requires, get_bloginfo('version'), '<' ) ) {
			$res = new stdClass();
			$res->slug = 'https://www.projectpanorama.com/info.json';
			$res->plugin = 'psp-woocommerce/class-panorama-woocommerce.php'; // it could be just YOUR_PLUGIN_SLUG.php if your plugin doesn't have its own directory
			$res->new_version = $remote->version;
			$res->tested = $remote->tested;
			$res->package = $remote->download_url;
			$res->url = $remote->homepage;
				$transient->response[$res->plugin] = $res;
				//$transient->checked[$res->plugin] = $remote->version;
			}
	}

	return $transient;

}

function psp_woocommerce_after_update( $upgrader_object, $options ) {
	if ( $options['action'] == 'update' && $options['type'] === 'plugin' )  {
		// just clean the cache when new plugin version is installed
		delete_transient( 'psp_upgrade_psp_woocommerce' );
	}
}
