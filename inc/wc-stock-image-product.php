<?php

require_once 'stock-image-product-trait.php';

/**
 * Class WC_Product_Stock_Image
 *
 */
class WC_Product_Stock_Image extends \WC_Product_Variable {

	use WooStockImageProduct\Stock_Image_Product;

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'stock_image_product';
	}

	/**
	 * get_image
	 *
	 * Overrides WP_Product->get_image and returns adobe stock image from cache
	 *
	 * @param string $size (default: 'woocommerce_thumbnail').
	 * @param array $attr Image attributes.
	 * @param bool $placeholder True to return $placeholder if no image is found, or false to return an empty string.
	 *
	 * @return string
	 */
	public function get_image( $size = 'woocommerce_thumbnail', $attr = array(), $placeholder = true ) {

		$image = parent::get_image( $size, $attr, $placeholder );

		if ( ! empty( $this->get_stock_image() ) ) {

			if ($image !== '') {
				$image = \WooStockImageProduct\replace_img_attrs( $image, $this );
			} else {
				$image = sprintf("<img src=\"%s\" alt=\"%s\">", $this->stock_image->thumbnail_url, $this->stock_image->title );
			}

		}

		return $image;
	}

	/**
	 * get_permalink
	 *
	 * Add the image id to the permalink
	 *
	 * @param $item_object TODO for variations - see parent class
	 *
	 * @return string
	 */
	public function get_permalink( $item_object = null ) {
		$url = get_permalink( $this->get_id() );

		if ( $image_id = $this->get_stock_image_id() ) {
			$url = add_query_arg( 'image_id', $image_id, $url );
		}

		return $url;
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		return apply_filters( 'woocommerce_product_add_to_cart_url', $this->get_permalink(), $this );
	}

	/**
	 * get_available_variation
	 *
	 * overrides parent::get_available_variation if no image_id - this prevents errors in template
	 *
	 * @since  2.4.0
	 * @param  WC_Product $variation Variation product object or ID.
	 * @return array|bool
	 */
	public function get_available_variation( $variation ) {

		if ( $variation->get_image_id() ) {
			parent::get_available_variation( $variation );
		}

		if ( is_numeric( $variation ) ) {
			$variation = wc_get_product( $variation );
		}
		if ( ! $variation instanceof WC_Product_Variation ) {
			return false;
		}
		// See if prices should be shown for each variation after selection.
		$show_variation_price = apply_filters( 'woocommerce_show_variation_price', $variation->get_price() === '' || $this->get_variation_sale_price( 'min' ) !== $this->get_variation_sale_price( 'max' ) || $this->get_variation_regular_price( 'min' ) !== $this->get_variation_regular_price( 'max' ), $this, $variation );

		return apply_filters(
			'woocommerce_available_variation', array(
			'attributes'            => $variation->get_variation_attributes(),
			'availability_html'     => wc_get_stock_html( $variation ),
			'backorders_allowed'    => $variation->backorders_allowed(),
			'dimensions'            => $variation->get_dimensions( false ),
			'dimensions_html'       => wc_format_dimensions( $variation->get_dimensions( false ) ),
			'display_price'         => wc_get_price_to_display( $variation ),
			'display_regular_price' => wc_get_price_to_display( $variation, array( 'price' => $variation->get_regular_price() ) ),
			'image'                 => null, // BW - Only change to parent is here
			'image_id'              => $variation->get_image_id(),
			'is_downloadable'       => $variation->is_downloadable(),
			'is_in_stock'           => $variation->is_in_stock(),
			'is_purchasable'        => $variation->is_purchasable(),
			'is_sold_individually'  => $variation->is_sold_individually() ? 'yes' : 'no',
			'is_virtual'            => $variation->is_virtual(),
			'max_qty'               => 0 < $variation->get_max_purchase_quantity() ? $variation->get_max_purchase_quantity() : '',
			'min_qty'               => $variation->get_min_purchase_quantity(),
			'price_html'            => $show_variation_price ? '<span class="price">' . $variation->get_price_html() . '</span>' : '',
			'sku'                   => $variation->get_sku(),
			'variation_description' => wc_format_content( $variation->get_description() ),
			'variation_id'          => $variation->get_id(),
			'variation_is_active'   => $variation->variation_is_active(),
			'variation_is_visible'  => $variation->variation_is_visible(),
			'weight'                => $variation->get_weight(),
			'weight_html'           => wc_format_weight( $variation->get_weight() ),
		), $this, $variation
		);
	}


}