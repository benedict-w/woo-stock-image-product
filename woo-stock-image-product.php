<?php
/**
 * Plugin Name: Woo Stock Image Product
 *
 * Plugin URI:
 *
 * Description: A plugin for dynamically replacing a product image with a Stock Image found using Adobe Stock API.
 *
 * Version: 1.0.0
 * Author: Benedict Wallis
 * Author URI: https://www.benedict-wallis.com
 * Developer: Benedict Wallis
 * Developer URI: https://www.benedict-wallis.com
 * Text Domain: woo-stock-image-product"
 * Domain Path: /languages
 *
 * Woo:
 * WC requires at least: 3.4.5
 * WC tested up to: 3.4.5
 *
 * Copyright: © 2018 Benedict Wallis
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace WooStockImageProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly
}

require_once( 'inc/util.php' );
require_once( 'inc/settings.php' );
require_once( 'inc/product.php' );
require_once( 'inc/hooks-wp-query.php' );
require_once( 'inc/hooks-woocommerce.php' );
require_once( 'inc/adobe-oauth.php' );
require_once( 'inc/adobe-stock-api.php' );

require_once( 'inc/cropper.php' );

require_once( 'shortcode-archive.php' );
require_once( 'widget-search.php' );


// plugin name, text-domain, etc.
const PLUGIN_NAME = 'woo-stock-image-product';

if ( ! class_exists( 'WooStockImageProduct\Plugin' ) ) :

	/**
	 * WooStockImageProduct\Plugin
	 *
	 * @class WooStockImageProduct\Plugin
	 *
	 */
	class Plugin {

		// paths
		public static $plugin_path;

		/**
		 * Plugin constructor.
		 */
		public function __construct() {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			add_shortcode( 'stock_archive', array(
				$this,
				'stock_archive_shortcode'
			), 10, 2 );
		}

		/**
		 * get_plugin_path
		 *
		 * @return mixed
		 */
		public static function get_plugin_path() {
			if ( ! static::$plugin_path ) {
				static::$plugin_path = dirname( __FILE__ );
			}

			return static::$plugin_path;
		}

		/**
		 * admin_notices
		 *
		 */
		public function admin_notices() {
			// Check if WooCommerce is active
			if ( !self::is_woocommerce_active() ) {

				printf( '<div class="notice notice-error"><p>%1$s</p></div>',
					esc_html( __( 'The Woo Stock Image Product plugin requires that WooCommerce has been installed and activated.', PLUGIN_NAME ) ) );
			}

			// Check if API key present
			if ( ! get_option( 'wc_settings_stock_images_adobe_api_key' ) ) {
				printf( '<div class="notice notice-error"><p>%1$s <a href="%2$sadmin.php?page=wc-settings&tab=stock_images">%3$s</a></p></div>',
					esc_html( __( 'The Woo Stock Image Product plugin requires an Adobe Stock API Key.', PLUGIN_NAME ) ),
					get_admin_url(),
					esc_html( __( 'Settings', PLUGIN_NAME ) ) );

			}
		}

		/**
		 * stock_archive_shortcode
		 *
		 * @hook stock_archive
		 *
		 * @return string
		 */
		public function stock_archive_shortcode( $attrs ) {

			if ( class_exists( 'woocommerce' ) ) {

				$handler = new ArchiveShortcode( $attrs );

				return $handler->get_content();
			}
		}

		/**
		 * is_woocommerce_active
		 *
		 * @return bool
		 */
		public static function is_woocommerce_active() {

			return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );

		}

	}

endif;

new Plugin();
new Product();
new Shop();
new SearchWidget();
new Cropper();
new AdobeOAuth();