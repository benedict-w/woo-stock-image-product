<?php
/**
 * Shop
 *
 * Custom functionality for modifying WooCommerce shop features
 */

namespace WooStockImageProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly
}

/**
 * Product
 *
 * @class WooStockImageProduct\Shop
 *
 */
class Shop {

	/**
	 * Shop constructor
	 *
	 */
	public function __construct() {

		add_filter( 'rewrite_rules_array', array( $this, 'add_rewrite_rules' ) );

		add_filter( 'woocommerce_order_item_product', array(
			$this,
			'order_item_stock_image_product'
		), 10, 2 );

		add_filter( 'woocommerce_order_item_get_name', array(
			$this,
			'stock_image_product_order_item_get_name'
		), 10, 2 );

		add_filter( 'woocommerce_order_item_name', array(
			$this,
			'stock_image_product_order_item_name'
		), 10, 2 );

		add_filter( 'woocommerce_order_item_thumbnail', array(
			$this,
			'stock_image_product_order_item_thumbnail'
		), 10, 2 );

		add_filter( 'woocommerce_order_item_permalink', array(
			$this,
			'stock_image_product_order_item_permalink'
		), 10, 3 );

		add_filter( 'woocommerce_order_item_display_meta_key', array(
			$this,
			'display_order_item_meta_keys',
		), 10, 3 );

		add_filter( 'woocommerce_order_item_display_meta_value', array(
			$this,
			'display_order_item_meta_values'
		), 10, 3 );

		add_filter( 'woocommerce_hidden_order_itemmeta', array(
			$this,
			'hide_stock_image_product_item_meta'
		), 10, 1 );

		if ( ! is_admin() ) {

			add_action ( 'woocommerce_single_product_summary', array(
				$this, 'add_product_switch_dropdown'
			), 30);

			add_filter( 'woocommerce_redirect_single_search_result', array(
				$this,
				'redirect_single_search_result'
			) );

			add_action( 'the_post', array(
				$this,
				'setup_product_data'
			), 20 ); // must fire after wc_setup_product_data

			add_action( 'the_post', array(
				$this,
				'setup_post_data'
			), 10 );

			add_filter( 'woocommerce_before_shop_loop', array(
				$this,
				'remove_result_count'
			), 10, 1 );

			add_filter( 'woocommerce_before_shop_loop', array(
				$this,
				'remove_catalog_ordering'
			), 10, 1 );

			add_filter( 'woocommerce_loop_product_link', array(
				$this,
				'stock_image_product_link'
			), 10, 2 );

			add_filter( 'post_type_link', array(
				$this,
				'stock_image_post_link'
			), 10, 3 );

			add_filter( 'wp_get_attachment_image_src', array(
				$this,
				'get_stock_image_src'
			), 10, 4 );

			add_filter( 'woocommerce_placeholder_img_src', array(
				$this,
				'replace_placeholder_src'
			), 10, 1 );

			add_filter( 'the_title', array(
				$this,
				'stock_image_product_title'
			), 100, 1 );

			add_filter( 'body_class', array(
				$this,
				'add_stock_image_product_body_class'
			), 10, 1 );

			add_filter( 'woocommerce_add_cart_item', array(
				$this,
				'add_stock_image_product_cart_item'
			), 10, 1 );

			add_filter( 'woocommerce_cart_item_thumbnail', array(
				$this,
				'stock_image_product_cart_item_thumbnail'
			), 10, 3 );

			add_filter( 'woocommerce_cart_item_name', array(
				$this,
				'stock_image_product_cart_item_name'
			), 10, 3 );

			add_filter( 'woocommerce_cart_item_permalink', array(
				$this,
				'stock_image_product_cart_item_permalink'
			), 10, 3 );

			add_action( 'woocommerce_checkout_create_order_line_item', array(
				$this,
				'add_stock_image_to_order'
			), 10, 4 );

			add_filter( 'woocommerce_add_cart_item_data', array(
				$this,
				'individual_stock_image_product_cart_items'
			), 10, 2 );

			add_action( 'woocommerce_payment_complete', array(
				$this,
				'get_stock_image_license'
			), 10, 1 );

		}
	}

	/**
	 * is_post_stock_image_product
	 *
	 * Returns \WC_Stock_Image_Product if true otherwise false
	 *
	 * @param \WP_Post $post
	 *
	 * @return bool|false|\WC_Product
	 */
	protected function is_post_stock_image_product( \WP_Post $post ) {
		if ( $post && $post->post_type === 'product' ) {

			$product = wc_get_product( $post );

			if ( is_object( $product ) && $product->is_type( 'stock_image_product' ) ) {
				return $product;
			}

		}

		return false;
	}

	/**
	 * post_to_stock_image_product
	 *
	 * Checks if the post object from WP_Query has had the stock_image attached
	 *
	 * @param \WP_Post $post
	 *
	 * @return \WC_Product
	 */
	protected function post_to_stock_image_product( \WP_Post $post ) {

		if ( $product = $this->is_post_stock_image_product( $post ) ) {

			if ( isset( $post->stock_image ) ) {
				$product->set_stock_image( $post->stock_image );
			}
		}

		return $product;
	}

	/**
	 * add_product_switch_dropdown
	 */
	public function add_product_switch_dropdown() {

		if ( is_product() ) {

			global $post;

			if ( $product = $this->is_post_stock_image_product( $post ) ) {

				$products = wc_get_products( array(
					'type'    => 'stock_image_product',
				) );

				ob_start();

				if ( count( $products ) ) : ?>
					<label for="stock-image-product-select"><?php _e("Choose Product", PLUGIN_NAME); ?></label>
					<select id="stock-image-product-select">
						<?php foreach ( $products as &$item ) : ?>
							<option value="<?php echo get_permalink($item->get_id()); ?>" <?php if($item->get_id() === $post->ID) :?>selected="selected"<?php endif; ?>><?php echo $item->get_title(); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif;

				$html = ob_get_contents();
				ob_end_clean();

				$html = apply_filters('stock_image_product_select', $html, $product, $products);

				echo $html;
			}
		}

	}

	/**
	 * add_stock_image_product_body_class
	 *
	 * @hook body_class
	 *
	 * @param $classes
	 *
	 * @return array
	 */
	public function add_stock_image_product_body_class( $classes ) {

		if ( is_product() ) {

			global $post;

			if ( $product = $this->is_post_stock_image_product( $post ) ) {
				$classes[] = 'stock-image-product';
			}
		}

		return $classes;
	}

	/**
	 * redirect_single_search_result
	 *
	 * WooCommerce directs single search result to a product only, we need to display dynamic products for
	 * each stock image returned so disable this feature.
	 *
	 * @hook woocommerce_redirect_single_search_result
	 *
	 * @return bool
	 */
	public function redirect_single_search_result() {

		global $wp_query;
		if ( Query::is_stock_image_query( $wp_query ) ) {
			return false;
		}

	}

	/**
	 * remove_result_count
	 *
	 * TODO could make settings option
	 *
	 * Remove the "Showing single result count" text from WooCommerce shop loop for stock_image_products
	 *
	 */
	public function remove_result_count() {
		global $wp_query;
		if ( Query::is_stock_image_query( $wp_query ) ) {
			remove_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 20 );
			remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		}
	}

	/**
	 * remove_catalog_ordering
	 *
	 * TODO could make settings option
	 *
	 * Remove the catalog ordering for stock_image_products, nb - we can't group by popularity, etc.
	 *
	 */
	public function remove_catalog_ordering() {
		global $wp_query;
		if ( Query::is_stock_image_query( $wp_query ) ) {
			remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
			remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
		}
	}

	/**
	 * setup_product_data
	 *
	 * Modify the WooCommerce product data by setting the stock image from the original post pulled from WP_Query mod
	 *
	 * @hook the_post
	 *
	 * @param $post
	 */
	public function setup_product_data( &$post ) {

		if ( isset( $post->stock_image ) && isset( $GLOBALS['product'] ) ) {
			$product = $GLOBALS['product'];
			if ( is_object( $product ) && $product->is_type( 'stock_image_product' ) ) {
				$product->set_stock_image( $post->stock_image );
			}
		}
	}

	/**
	 * setup_post_data
	 *
	 * When setting up the post we need to get set the stock_image if this is a \WC_Product_Stock_Image
	 *
	 * @hook the_post
	 *
	 * @param $post
	 */
	public function setup_post_data( &$post ) {

		if ( $post->post_type === 'product' ) {
			$product = wc_get_product( $post );
			if ( $product->is_type( 'stock_image_product' ) ) {
				if ( ! isset( $post->stock_image ) ) {
					$stock_image_product = new \WC_Product_Stock_Image( $post );
					$post->stock_image   = $stock_image_product->get_stock_image();
				}

			}
		}

	}

	/**
	 * stock_image_product_link
	 *
	 * @hook woocommerce_loop_product_link
	 *
	 * @param $url
	 * @param $product
	 *
	 * @return string
	 */
	public function stock_image_product_link( $url, $product ) {

		if ( $product->is_type( 'stock_image_product' ) ) {
			$url = $product->get_permalink();
		}

		return $url;
	}

	/**
	 * stock_image_post_link
	 *
	 * @hook post_type_link
	 *
	 * @param $permalink
	 * @param $post
	 * @param $leavename
	 *
	 * @return string
	 */
	public function stock_image_post_link( $permalink, $post, $leavename ) {

		if ( is_object( $post ) && $post->post_type === 'product' && isset( $post->stock_image ) ) {

			$product = wc_get_product( $post );

			if ( is_object( $product ) && $product->is_type( 'stock_image_product' ) ) {

				$permalink = add_query_arg( 'image_id', $post->stock_image->id, $permalink );

			}
		}

		return $permalink;
	}

	/**
	 * add_rewrite_rules
	 *
	 * @hook rewrite_rules_array
	 *
	 * @param $rules
	 *
	 * @return mixed
	 */
	public function add_rewrite_rules( $rules ) {

		$rules['image-products\/?$']                   = 'index.php?post_type=product&product_type=stock_image_product';
		$rules['image-products\/page\/([0-9]{1,})/?$'] = 'index.php?post_type=product&product_type=stock_image_product&paged=$matches[1]';

		return $rules;
	}

	/**
	 * get_stock_image_src
	 *
	 * Replaces the featured image with the stock image if this is a stock_image_product
	 *
	 * @hook wp_get_attachment_image_src
	 *
	 * @param $image
	 * @param $attachment_id
	 * @param $size
	 * @param $icon
	 *
	 * @return mixed
	 */
	public function get_stock_image_src( $image, $attachment_id, $size, $icon ) {

		global $post;

		if ( is_object( $post ) && $post->post_type === 'product' && isset( $post->stock_image ) ) {

			$product = wc_get_product( $post );

			if ( is_object( $product ) && $product->is_type( 'stock_image_product' ) ) {
				if ( get_post_thumbnail_id( $post ) === $attachment_id ) { // replace featured image only
					$image[0] = $post->stock_image->thumbnail_url;
				}
			}
		}

		return $image;
	}

	/**
	 * replace_placeholder_src
	 *
	 * @hook woocommerce_placeholder_img_src
	 *
	 * @param $src
	 *
	 * @return string
	 */
	public function replace_placeholder_src( $src ) {

		if ( isset( $GLOBALS['post'] ) ) {
			$post = $GLOBALS['post'];

			if ( isset( $post->stock_image ) ) {
				$src = $post->stock_image->thumbnail_url;
			}
		}

		return $src;
	}


	/**
	 * stock_image_product_title
	 *
	 * @hook the_title
	 *
	 * @param $title
	 *
	 * @return string
	 */
	public function stock_image_product_title( $title ) {

		global $product;

		if ( is_object( $product ) && $product->is_type( 'stock_image_product' ) && $title === $product->get_title() ) {

			if ( $stock_image = $product->get_stock_image() ) {
				$title = format_stock_image_product_title( $product->get_title(), $stock_image->title );
			}

		}

		return $title;

	}

	/**
	 * individual_stock_image_product_cart_items
	 *
	 * We want each stock image product to form a separate cart row - this requires a unique key
	 * for each stock_image_product. So append the image_id to the product_id to group on image products.
	 *
	 * Also save the crop information about the item.
	 *
	 * TODO possible to somehow use the original key instead of product_id (can't see one!)
	 *
	 * @hook woocommerce_add_cart_item_data
	 *
	 * @param $cart_item_data
	 * @param $product_id
	 *
	 * @return mixed
	 */
	public function individual_stock_image_product_cart_items( $cart_item_data, $product_id ) {

		if ( $stock_image_id = filter_input( INPUT_GET, 'image_id', FILTER_SANITIZE_NUMBER_INT ) ) {
			$cart_item_data['stock_image_id'] = $stock_image_id;
			$cart_item_data['key']            = sprintf( "%s-%s", $product_id, $stock_image_id );
		}

		if ( isset( $_POST['crop-max-width'] ) ) {
			$cart_item_data['crop_max_width'] = filter_input( INPUT_POST, 'crop-max-width', FILTER_SANITIZE_NUMBER_INT );
		}

		if ( isset( $_POST['crop-height'] ) ) {
			$cart_item_data['crop_max_height'] = filter_input( INPUT_POST, 'crop-max-height', FILTER_SANITIZE_NUMBER_INT );
		}

		if ( isset( $_POST['crop-height'] ) ) {
			$cart_item_data['crop_height'] = filter_input( INPUT_POST, 'crop-height', FILTER_SANITIZE_NUMBER_INT );
		}

		if ( isset( $_POST['crop-width'] ) ) {
			$cart_item_data['crop_width'] = filter_input( INPUT_POST, 'crop-width', FILTER_SANITIZE_NUMBER_INT );
		}

		if ( isset( $_POST['crop-x'] ) ) {
			$cart_item_data['crop_x'] = filter_input( INPUT_POST, 'crop-x', FILTER_SANITIZE_NUMBER_INT );
		}

		if ( isset( $_POST['crop-y'] ) ) {
			$cart_item_data['crop_y'] = filter_input( INPUT_POST, 'crop-y', FILTER_SANITIZE_NUMBER_INT );
		}

		return $cart_item_data;
	}

	/**
	 * add_stock_image_product_cart_item
	 *
	 * Make sure product name is set for display in the cart
	 *
	 * @hook woocommerce_add_cart_item
	 *
	 * @param $cart_item_data
	 *
	 * @return mixed
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	public function add_stock_image_product_cart_item( $cart_item_data ) {

		if ( isset( $cart_item_data['data'] ) ) {

			$product = $cart_item_data['data'];

			if ( $product->is_type( 'stock_image_product' ) ) {
				if ( $stock_image = $product->get_stock_image( 24 * 60 * 60 ) ) { // save for 24 hours
					$product->set_name( format_stock_image_product_title( $product->get_name(), $stock_image->title ) );
				}
			}
		}

		return $cart_item_data;
	}

	/**
	 * stock_image_product_cart_item_thumbnail
	 *
	 * Replace the cart item image with the stock image
	 *
	 * @hook woocommerce_cart_item_thumbnail
	 *
	 * @param $cart_item_image
	 * @param $cart_item
	 * @param $cart_item_key
	 *
	 * @return mixed
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	public function stock_image_product_cart_item_thumbnail( $cart_item_image, $cart_item, $cart_item_key ) {

		if ( isset( $cart_item['data'] ) && isset( $cart_item['stock_image_id'] ) ) {

			if ( ! empty( $cart_item['variation_id'] ) ) {

				// is variation
				$image = AdobeStockApi::get_image_by_id( $cart_item['stock_image_id'] );

				// use this hook for stock image proxying in cropper
				$image = apply_filters( 'stock_image_object', $image );

				$cart_item_image = replace_img_src( $cart_item_image, $image->thumbnail_url );
				$cart_item_image = replace_img_srcset( $cart_item_image, $image->thumbnail_url );
				$cart_item_image = replace_img_alt( $cart_item_image, $image->title );

			} else {
				// is product
				$product = $cart_item['data'];

				if ( $product->is_type( 'stock_image_product' ) ) {
					$product->set_stock_image( AdobeStockApi::get_image_by_id( $cart_item['stock_image_id'] ) );
					$cart_item_image = replace_img_attrs( $cart_item_image, $product );
				}
			}
		}

		return $cart_item_image;
	}

	/**
	 * stock_image_product_cart_item_permalink
	 *
	 * Add the image_id to the cart item permalink
	 *
	 * @hook woocommerce_cart_item_permalink
	 *
	 * @param $cart_item_permalink
	 * @param $cart_item
	 * @param $cart_item_key
	 *
	 * @return string
	 */
	public function stock_image_product_cart_item_permalink( $cart_item_permalink, $cart_item, $cart_item_key ) {

		if ( isset( $cart_item['stock_image_id'] ) ) {
			$cart_item_permalink = add_query_arg( 'image_id', $cart_item['stock_image_id'], $cart_item_permalink );
		}

		return $cart_item_permalink;
	}

	/**
	 * stock_image_product_cart_item_name
	 *
	 * @hook woocommerce_cart_item_name
	 *
	 * @param $cart_item_name
	 * @param $cart_item
	 * @param $cart_item_key
	 *
	 * @return mixed
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	public function stock_image_product_cart_item_name( $cart_item_name, $cart_item, $cart_item_key ) {

		if ( isset( $cart_item['stock_image_id'] ) ) {

			if ( $stock_image = AdobeStockApi::get_image_by_id( $cart_item['stock_image_id'] ) ) {
				$cart_item_name = str_replace( $cart_item['data']->get_name(), format_stock_image_product_title( $cart_item['data']->get_name(), $stock_image->title ), $cart_item_name );
			}
		}

		return $cart_item_name;
	}

	/**
	 * add_stock_image_to_order
	 *
	 * Save the stock image product meta data to the order
	 *
	 * @hook woocommerce_checkout_create_order_line_item
	 *
	 * @param $item
	 * @param $cart_item_key
	 * @param $values
	 * @param $order
	 */
	public function add_stock_image_to_order( $item, $cart_item_key, $values, $order ) {

		if ( ! empty( $values['stock_image_id'] ) ) {

			$stock_image_id = $values['stock_image_id'];

			if ( $stock_image = AdobeStockApi::get_image_by_id( $stock_image_id ) ) {
				$item->add_meta_data( '_stock_image_id', $stock_image->id, true );
				$item->add_meta_data( '_stock_image_title', $stock_image->title, true );
				$item->add_meta_data( '_stock_image_url', $stock_image->details_url, true );
			}

			// save cropper info
			$item->add_meta_data( '_crop_max_width', $values['crop_max_width'] ?? '', true );
			$item->add_meta_data( '_crop_max_height', $values['crop_max_height'] ?? '', true );
			$item->add_meta_data( '_crop_width', $values['crop_width'] ?? '', true );
			$item->add_meta_data( '_crop_height', $values['crop_height'] ?? '', true );
			$item->add_meta_data( '_crop_x', $values['crop_x'] ?? '', true );
			$item->add_meta_data( '_crop_y', $values['crop_y'] ?? '', true );

		}

	}

	/**
	 * stock_image_product_order_item_thumbnail
	 *
	 * TODO not used in Avada!
	 *
	 * @hook woocommerce_order_item_thumbnail
	 *
	 * @param $var
	 * @param $item
	 *
	 * @return mixed
	 */
	public function stock_image_product_order_item_thumbnail( $var, $item ) {
		return $var;
	}

	/**
	 * stock_image_product_order_item_permalink
	 *
	 * TODO not used in Avada!
	 *
	 * @hook woocommerce_order_item_permalink
	 *
	 * @param $product_get_permalink_item
	 * @param $item
	 * @param $order
	 *
	 * @return mixed
	 */
	public function stock_image_product_order_item_permalink( $product_get_permalink_item, $item, $order ) {
		return $product_get_permalink_item;
	}

	/**
	 * stock_image_product_order_item_name
	 *
	 * Replace stock_image_product name in order details
	 *
	 * @hook woocommerce_order_item_name
	 *
	 * @param $item_name
	 * @param $item
	 *
	 * @return mixed
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	public function stock_image_product_order_item_name( $item_name, $item ) {

		if ( $stock_image_id = $item->get_meta( '_stock_image_id' ) ) {

			if ( $stock_image = AdobeStockApi::get_image_by_id( $stock_image_id ) ) {
				// $item_name = str_replace( $item->get_name(), format_stock_image_product_title( $item->get_name(), $stock_image->title ), $item_name );
			}
		}

		return $item_name;
	}

	/**
	 * stock_image_product_order_item_get_name
	 *
	 * return the formatted stock_image_product name
	 *
	 * @hook woocommerce_order_item_get_name
	 *
	 * @param $item_name
	 * @param $item
	 *
	 * @return mixed
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	public function stock_image_product_order_item_get_name( $item_name, $item ) {

		if ( $stock_image_id = $item->get_meta( '_stock_image_id' ) ) {

			if ( $stock_image = AdobeStockApi::get_image_by_id( $stock_image_id ) ) {
				// $item_name = format_stock_image_product_title( $item_name, $stock_image->title );
			}
		}

		return $item_name;
	}

	/**
	 * order_item_stock_image_product
	 *
	 * @param $product
	 * @param $item
	 *
	 * @return mixed
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	public function order_item_stock_image_product( $product, $item ) {

		if ( $product->is_type( 'stock_image_product' ) ) {
			if ( $stock_image_id = $item->get_meta( '_stock_image_id' ) ) {
				$product->set_stock_image( AdobeStockApi::get_image_by_id( $stock_image_id ) );
			}
		}

		if ( $product->is_type( 'variation' ) ) {
			if ( $stock_image_id = $item->get_meta( '_stock_image_id' ) ) {
				$product = new \WC_Product_Stock_Image_Variation( $product );

				$image = AdobeStockApi::get_image_by_id( $stock_image_id );
				$image = apply_filters('stock_image_object', $image);
				$product->set_stock_image( $image );
			}
		}

		return $product;
	}

	/**
	 * display_order_item_meta_keys
	 *
	 * @hook woocommerce_order_item_display_meta_key
	 *
	 * @param $key
	 * @param $meta
	 * @param $item
	 *
	 * @return string
	 */
	public function display_order_item_meta_keys( $key, $meta, $item ) {

		switch ( $meta->key ) {

			case '_stock_image_id' :
				$key = __( "Stock Image ID", PLUGIN_NAME );
				break;
			case '_stock_image_url' :
				$key = __( "Stock Image URL", PLUGIN_NAME );
				break;
			case '_stock_image_title' :
				$key = __( "Stock Image", PLUGIN_NAME );
				break;
			case '_crop_x' :
				$key = __( "Crop Start X (px)", PLUGIN_NAME );
				break;
			case '_crop_y' :
				$key = __( "Crop Start Y (px)", PLUGIN_NAME );
				break;
			case '_crop_width' :
				$key = __( "Crop Width (px)", PLUGIN_NAME );
				break;
			case '_crop_height' :
				$key = __( "Crop Height (px)", PLUGIN_NAME );
				break;
			case '_crop_max_width' :
				$key = __( "Crop Orignal Width (px)", PLUGIN_NAME );
				break;
			case '_crop_max_height' :
				$key = __( "Crop Original Height (px)", PLUGIN_NAME );
				break;

		}

		return $key;
	}

	/**
	 * display_order_item_meta_values
	 *
	 * @hook woocommerce_order_item_display_meta_value
	 *
	 * @param $value
	 * @param $meta
	 * @param $item
	 *
	 * @return string
	 */
	public function display_order_item_meta_values( $value, $meta, $item ) {

		switch ( $meta->key ) {

			case '_stock_image_title' :
				if ( $link = $item->get_meta( '_stock_image_url' ) ) {
					$value = sprintf( "<a href=\"%s\" target=\"_blank\">%s</a>", $link, $value );
				}

				break;

			case '_crop_width' :
				if ( $max = $item->get_meta( '_crop_max_width' ) ) {
					$value = sprintf( "%d / %d", $value, $max );
				}

				break;

			case '_crop_height' :
				if ( $max = $item->get_meta( '_crop_max_height' ) ) {
					$value = sprintf( "%d / %d", $value, $max );
				}

				break;

		}

		return $value;
	}

	/**
	 * hide_stock_image_product_item_meta
	 *
	 * Hide the URL, as we will just add a link to it
	 *
	 * @hook woocommerce_hidden_order_itemmeta
	 *
	 * @param $hidden_meta
	 *
	 * @return array
	 */
	public function hide_stock_image_product_item_meta( $hidden_meta ) {

		// $hidden_meta[] = '_stock_image_url';
		$hidden_meta[] = '_crop_max_width';
		$hidden_meta[] = '_crop_max_height';

		return $hidden_meta;

	}

	/**
	 * get_stock_image_license
	 *
	 *
	 * @hook woocommerce_payment_complete
	 *
	 * @param $order_id
	 */
	public function get_stock_image_license( $order_id ) {

		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item_id => $item ) {

			if ($stock_image_id = $item->get_meta( '_stock_image_id' )) {

				if ( AdobeStockApi::get_content_license( $stock_image_id ) ) {

					if ( Settings::get_save_image_to_media_library() ) {

						$stock_image_id = $item->get_meta( '_stock_image_id' );

						$crop_max_width  = $item->get_meta( '_crop_max_width' );
						$crop_max_height = $item->get_meta( '_crop_max_height' );

						$crop_width  = $item->get_meta( '_crop_width' );
						$crop_height = $item->get_meta( '_crop_height' );

						$crop_x = $item->get_meta( '_crop_x' );
						$crop_y = $item->get_meta( '_crop_y' );

						$this->download_stock_image_to_media_library( $stock_image_id, $item->get_id(), $crop_max_width, $crop_max_height, $crop_width, $crop_height, $crop_x, $crop_y );

					}
				}
			}

		}

	}

	/**
	 * download_stock_image_to_media_library
	 *
	 * @param $image_id
	 * @param $order_id
	 *
	 * @return
	 */
	protected function download_stock_image_to_media_library( $image_id, $item_id, $crop_max_width, $crop_max_height, $width, $height, $x, $y ) {

		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		$url = AdobeStockApi::get_download_asset_url( $image_id );

		// TODO better to save manually and then attach (to stop thumbnails being processed twice)

		if ( $attachment_id = media_sideload_image( $url, $item_id, '','id' ) ) {

			if ( $attachment = get_attached_file( $attachment_id ) ) {

				$imagick = new \Imagick( $attachment );

				$dimensions = $imagick->getImageGeometry();

				$scale_x = ( $dimensions['width'] / ( $crop_max_width ?: 1 ) );
				$scale_y = ( $dimensions['height'] / ( $crop_max_height ?: 1 ) );

				$width  = $width * $scale_x;
				$height = $height * $scale_y;

				$x = $x * $scale_x;
				$y = $y * $scale_y;

				$imagick->cropImage( $width, $height, $x, $y );
				$imagick->writeImage( $attachment );

				$imagick->clear();

				set_post_thumbnail( $item_id, $attachment_id );

				// refreshes thumbnails
				wp_generate_attachment_metadata( $attachment_id, $attachment);

			}

		}


	}


}