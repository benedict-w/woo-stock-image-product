<?php

namespace WooStockImageProduct;

/**
 * Trait Stock_Image_Product
 *
 */
trait Stock_Image_Product {

	/**
	 * $stock_image
	 *
	 * @var null|AdobeStock\Api\Models\StockFile
	 *
	 */
	protected $stock_image = null;

	/**
	 * $stock_image_id
	 *
	 * @var null|int
	 */
	protected $stock_image_id = null;

	/**
	 * get_stock_image_id
	 *
	 * @return int
	 */
	public function get_stock_image_id() {

		if ( empty( $this->stock_image_id ) ) {

			if ($this->stock_image) {
				$this->stock_image_id = $this->stock_image->id;
			}

			if ( $id = filter_input( INPUT_GET, 'image_id', FILTER_SANITIZE_NUMBER_INT ) ) {
				$this->stock_image_id = intval( $id );
			}
		}

		return $this->stock_image_id;
	}

	/**
	 * get_stock_image
	 *
	 * @param $transient - persist to transients by setting val in seconds
	 *
	 * @return
	 */
	public function get_stock_image( $transient = 0 ) {

		if ( empty( $this->stock_image ) ) {
			if ( $image_id = $this->get_stock_image_id() ) {
				try {
					$this->stock_image = \WooStockImageProduct\AdobeStockApi::get_image_by_id( $image_id, $transient );
				} catch ( Exception $ex ) {
					// TODO
				}
			}
		}

		return $this->stock_image;
	}

	/**
	 * set_stock_image
	 *
	 * @param \AdobeStock\Api\Models\StockFile $stock_image
	 */
	public function set_stock_image( \AdobeStock\Api\Models\StockFile $stock_image ) {
		$this->stock_image = $stock_image;
		$this->stock_image_id = $stock_image->id;
	}

}