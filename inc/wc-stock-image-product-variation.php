<?php

/**
 * Class WC_Product_Stock_Image
 *
 */
class WC_Product_Stock_Image_Variation extends \WC_Product_Variation {

	use WooStockImageProduct\Stock_Image_Product;

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

}