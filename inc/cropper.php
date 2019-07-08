<?php

/**
 * Cropper
 *
 * Sets up the image cropper using data from product / variation dimesions fields.
 *
 * Front-end cropping library:
 *
 * https://jamesooi.design/Croppr.js/
 * https://github.com/jamesssooi/Croppr.js
 *
 */

namespace WooStockImageProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly
}

/**
 * Cropper
 *
 * @class WooStockImageProduct\Cropper
 *
 */
class Cropper {

	private $handle = 'stock-image-cropper';

	/**
	 * Cropper constructor
	 */
	public function __construct() {

		// add variation settings
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'crop_settings_fields' ), 10, 3 );
		// save variation Settings
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_crop_settings_fields' ), 10, 2 );

		add_filter( 'woocommerce_available_variation', array(
			$this,
			'add_crop_settings_to_available_variation'
		), 10, 3 );

		// setup front-end cropping
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// because of CORS we need to proxy the image url to be able to crop in HTML5 canvas
		// JS Error - "DOMException: Failed to execute 'toDataURL' on 'HTMLCanvasElement': Tainted canvases may not be exported."
		add_action( 'init', array( $this, 'proxy_image_url' ) );

		// we need to proxy the url
		add_filter( 'stock_image_object', array( $this, 'add_proxy_url' ) );

	}

	/**
	 * enqueue_scripts
	 *
	 * Enque scripts, styles, and data for front-end cropping
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

		// product page
		if ( is_product() ) {

			global $product;

			if ( ! is_object( $product ) ) {
				$product = \wc_get_product( get_the_ID() );
			}

			if ( $product->is_type( 'stock_image_product' ) ) {

				$this->enqueue_assets();

				// localize script data

				$ratios = array();

				foreach ( $product->get_available_variations() as $variation_data ) {

					if ( isset( $variation_data['crop_ratio_x'] ) && isset( $variation_data['crop_ratio_y'] ) ) {
						$ratios[ $variation_data['variation_id'] ] = $variation_data['crop_ratio_y'] / $variation_data['crop_ratio_x'];
					}
				}

				wp_localize_script( $this->handle, 'stock_image_cropper_settings', array(
					'ratios' => $ratios,
				) );
			}
		}

		$rows = array();

		if ( is_cart() || is_checkout() ) {

			if ( ! empty( $GLOBALS['woocommerce']->cart ) ) {

				$cart = $GLOBALS['woocommerce']->cart;

				$rows = $cart->get_cart();

			}

		}

		if ( is_order_received_page() ) {

			if ( ! empty( $GLOBALS['order-received'] ) ) {

				$order_id = $GLOBALS['order-received'];

				$order = wc_get_order( $order_id );

				$rows = $order->get_items();

			}

		}

		if ( $rows ) {

			$items = array();

			// localize script data

			$this->enqueue_assets();

			$i = 0;
			foreach ( $rows as &$item ) {

				if ( ! empty( $item['stock_image_id'] ) ) {

					$items[] = array(
						'index'          => $i,
						'stock_image_id' => intval( $item['stock_image_id'] ),
						'max_width'      => intval( $item['crop_max_width'] ?: 0 ),
						'max_height'     => intval( $item['crop_max_height'] ?: 0 ),
						'width'          => intval( $item['crop_width'] ?: 0 ),
						'height'         => intval( $item['crop_height'] ?: 0 ),
						'x'              => intval( $item['crop_x'] ?: 0 ),
						'y'              => intval( $item['crop_y'] ?: 0 ),
					);
				}

				$i ++;
			}

			wp_localize_script( $this->handle, 'stock_image_cropper_settings', array(
				'items' => $items,
			) );

		}

	}

	/**
	 * enqueue_assets
	 *
	 */
	private function enqueue_assets() {

		$base     = '/woo-stock-image-product/assets/';
		$path_js  = plugins_url( sprintf( '%s/js/dist/stock-image-cropper%s.js', $base, WP_DEBUG ? '' : '.min' ) );
		$path_css = plugins_url( sprintf( '%s/css/styles.css', $base ) );

		$version = '0.1';

		wp_register_script( $this->handle, $path_js, array( 'jquery' ), $version, false );

		wp_enqueue_script( $this->handle );

		wp_register_style( $this->handle, $path_css, array(), $version, 'screen' );

		wp_enqueue_style( $this->handle );
	}


	/**
	 * Create new fields for variations
	 *
	 * @param $loop
	 * @param $variation_data
	 * @param $variation
	 */
	public function crop_settings_fields( $loop, $variation_data, $variation ) {

		if ( $product = wc_get_product() ) {

			if ( $product->is_type( 'stock_image_product' ) ) {

				woocommerce_wp_text_input(
					array(
						'id'                => '_crop_ratio_x[' . $variation->ID . ']',
						'label'             => __( "Crop Ratio X", PLUGIN_NAME ),
						'desc_tip'          => 'true',
						'description'       => __( "Enter the crop x-axis dimensions", PLUGIN_NAME ),
						'value'             => get_post_meta( $variation->ID, '_crop_ratio_x', true ),
						'custom_attributes' => array(
							'step' => '1',
							'min'  => '0'
						)
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_crop_ratio_y[' . $variation->ID . ']',
						'label'             => __( "Crop Ratio Y", PLUGIN_NAME ),
						'desc_tip'          => 'true',
						'description'       => __( "Enter the crop y-axis dimensions", PLUGIN_NAME ),
						'value'             => get_post_meta( $variation->ID, '_crop_ratio_y', true ),
						'custom_attributes' => array(
							'step' => '1',
							'min'  => '0'
						)
					)
				);
			}
		}
	}

	/**
	 * save_variation_settings_fields
	 *
	 * @param $post_id
	 */
	public function save_crop_settings_fields( $post_id ) {

		if ( isset( $_POST['_crop_ratio_x'][ $post_id ] ) ) {
			update_post_meta( $post_id, '_crop_ratio_x', intval( $_POST['_crop_ratio_x'][ $post_id ] ) );
		}
		if ( isset( $_POST['_crop_ratio_y'][ $post_id ] ) ) {
			update_post_meta( $post_id, '_crop_ratio_y', intval( $_POST['_crop_ratio_y'][ $post_id ] ) );
		}

	}

	/**
	 * add_crop_settings_to_available_variation
	 *
	 * @param $variations
	 * @param $product
	 * @param $variation
	 *
	 * @return array
	 */
	public function add_crop_settings_to_available_variation( $variation_data, $product, $variation ) {
		foreach ( $variation->get_meta_data() as $meta_data ) {

			if ( ! empty( $meta_data ) ) {

				if ( $meta_data->key === '_crop_ratio_x' ) {
					$data                           = $meta_data->get_data();
					$variation_data['crop_ratio_x'] = $data['value'] ?? '';
				}

				if ( $meta_data->key === '_crop_ratio_y' ) {
					$data                           = $meta_data->get_data( '_crop_ratio_y' );
					$variation_data['crop_ratio_y'] = $data['value'] ?? '';
				}

			}

		}

		return $variation_data;
	}

	/**
	 * proxy_image_url
	 *
	 * @hook init
	 *
	 */
	public function proxy_image_url() {

		$redirect = plugins_url( 'woo-stock-image-product/image-proxy.php?id=$1' );

		$redirect = str_replace( home_url(), '', $redirect );

		add_rewrite_rule( 'stock-images/(.+)/?', $redirect, 'top' );

	}

	/**
	 * add_proxy_url
	 *
	 * image_id already set as query var in hooks-wp-query, so we just want a flag to check on init if we are proxying
	 *
	 * @hook stock_image_object
	 *
	 */
	public function add_proxy_url( $image ) {

		$image->thumbnail_url = home_url( sprintf( "stock-images/%d", $image->id ) );

		return $image;

	}


}