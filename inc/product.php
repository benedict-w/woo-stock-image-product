<?php
/**
 * Product
 */

namespace WooStockImageProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly
}

/**
 * Product
 *
 * @class WooStockImageProduct\Product
 *
 */
class Product {

	/**
	 * Product constructor
	 */
	public function __construct() {
		add_filter( 'product_type_selector', array( $this, 'add_custom_stock_image_product_type' ) );
		add_action( 'init', array( $this, 'create_custom_stock_image_product_type' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'stock_image_product_class' ), 10, 2 );
		add_action( 'admin_footer', array( $this, 'custom_js' ) );

		// variable product
		add_filter( 'woocommerce_data_stores', array( $this, 'stock_image_product_data_store' ) );
		add_action( 'woocommerce_stock_image_product_add_to_cart', array(
			$this,
			'stock_image_product_add_to_cart_button'
		) );

		// hook variable product handler when adding to cart, see -
		// https://stackoverflow.com/questions/51316906/how-to-add-a-custom-woocommerce-product-type-that-extends-wc-product-variable-to
		add_action( 'woocommerce_add_to_cart_handler', array( $this, 'stock_image_add_to_cart_handler' ), 10, 1 );

		// template hooks
		add_action( 'woocommerce_stock_image_product_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
	}

	/**
	 * add_custom_product_type
	 *
	 * @param $types
	 *
	 * @return mixed
	 */
	public function add_custom_stock_image_product_type( $types ) {
		$types['stock_image_product'] = __( "Stock image product", PLUGIN_NAME );

		return $types;
	}

	/**
	 * create_custom_product_type
	 */
	public function create_custom_stock_image_product_type() {
		require_once 'wc-stock-image-product.php';
		require_once 'wc-stock-image-product-variation.php';
	}

	/**
	 * woocommerce_product_class
     *
	 */
	public function stock_image_product_class( $classname, $product_type ) {
		if ( $product_type === 'stock_image_product' ) {
			$classname = 'WC_Product_Stock_Image';
		}

		// TODO - maybe too hacky!
		if ( $product_type === 'variation' && filter_input(INPUT_GET, 'image_id', FILTER_SANITIZE_NUMBER_INT) ) {
			// $classname = 'WC_Product_Stock_Image_Variation';
		}

		return $classname;
	}


	/**
	 * stock_image_product_data_store
	 *
	 * stock_image_product needs to use the variable product data store
	 *
	 * @param $stores
	 *
	 * @return mixed
	 */
	public function stock_image_product_data_store( $stores ) {

		$stores['product-stock_image_product'] = 'WC_Product_Variable_Data_Store_CPT';
		$stores['product-stock_image_product_variation'] = 'WC_Product_Variation_Data_Store_CPT';

		return $stores;
	}

	/**
	 * stock_image_product_add_to_cart_button
	 *
	 * Display the variable product add-to-cart form
	 *
	 */
	public function stock_image_product_add_to_cart_button() {
		do_action( 'woocommerce_variable_add_to_cart' );
	}

	/**
	 * stock_image_add_to_cart_handler
	 *
	 * Use the variation data store for handling add to cart.
	 *
     * @hook woocommerce_add_to_cart_handler
     *
	 * @param $product_type
	 *
	 * @return string
	 */
	public function stock_image_add_to_cart_handler( $product_type ) {
		if ( $product_type === 'stock_image_product' ) {
			$product_type = 'variation';
		}

		return $product_type;
	}

	/**
	 * custom_js
	 *
	 * Show pricing fields.
	 */
	public function custom_js() {

		if ( 'product' != get_post_type() ) :
			return;
		endif;

		?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {

                // Price tab
                // jQuery('.product_data_tabs .general_tab').addClass('show_if_stock_image_product').show();
                // jQuery('#general_product_data .pricing').addClass('show_if_stock_image_product').show();

                // Inventory tab
                jQuery('.inventory_options').addClass('show_if_stock_image_product').show();
                jQuery('#inventory_product_data ._manage_stock_field').addClass('show_if_variable_bulk').show();
                jQuery('#inventory_product_data ._sold_individually_field').parent().addClass('show_if_stock_image_product').show();
                jQuery('#inventory_product_data ._sold_individually_field').addClass('show_if_stock_image_product').show();

                // Tax Status
                jQuery('.product_data_tabs .general_tab').addClass('show_if_stock_image_product').show();
                jQuery('#general_product_data .show_if_variable').addClass('show_if_stock_image_product').show();

                // Variations tab
                jQuery('.product_data_tabs .variations_options').addClass('show_if_stock_image_product').show();
                jQuery('#general_product_data #variable_product_options').addClass('show_if_stock_image_product').show();
                jQuery('.product_data_tabs .variations_options').addClass('show_if_stock_image_product').show();
                jQuery('#woocommerce-product-data .enable_variation').addClass('show_if_stock_image_product').show();
                jQuery('.woocommerce_attribute_data .enable_variation').addClass('show_if_stock_image_product').show();

            });
        </script><?php
	}

}