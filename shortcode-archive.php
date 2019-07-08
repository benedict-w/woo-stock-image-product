<?php

namespace WooStockImageProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly
}

require_once __DIR__ . '/../woocommerce/includes/shortcodes/class-wc-shortcode-products.php';

/**
 * Code for displaying the Stock Image Search shortcode
 *
 */
if ( ! class_exists( 'WooStockImageProduct\ArchiveShortcode' ) ) :

	/**
	 * WooStockImageProduct\ArchiveShortcode
	 *
	 * @class WooStockImageProduct\ArchiveShortcode
	 *
	 */
	class ArchiveShortcode extends \WC_Shortcode_Products {

		/**
		 * Shortcode
		 *
		 * @var   string
		 */
		protected $type = 'stock_image_products';


		public function __construct( $attributes = array(), $type = 'products' ) {

			remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
			remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );

			remove_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 20 );
			remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );

			parent::__construct( $attributes, $type );
		}

		/**
		 * Parse attributes.
		 *
		 * @since  3.2.0
		 * @param  array $attributes Shortcode attributes.
		 * @return array
		 */
		protected function parse_attributes( $attrs ) {

			// we want to paginate by default
			if (!isset($attrs['paginate'])) {
				$attrs['paginate'] = true;
			}

			$parsed_attrs = parent::parse_attributes($attrs);

			if (isset($attrs['keyword'])) {
				$parsed_attrs['keyword'] = filter_var($attrs['keyword'], FILTER_SANITIZE_STRING);
			}

			return $parsed_attrs;
		}

		/**
		 * Parse query args.
		 *
		 * @since  3.2.0
		 * @return array
		 */
		protected function parse_query_args() {
			$query_args = parent::parse_query_args();

			$keyword = '';

			if (isset($this->attributes['keyword'])) {
				$keyword = $this->attributes['keyword'];
			}

			$query_args['s'] = $keyword;

			$query_args['product_type'] = 'stock_image_product';

			// this is set to 'ids' in parent which prevents the_posts from firing which is where we send the stock query
			$query_args['fields'] = null;

			return $query_args;
		}

		/**
		 * Slighy modification to parent get_query_results, only using posts not ids, as these have out stock images
		 *
		 * @return object Object with the following props; posts, per_page, found_posts, max_num_pages, current_page
		 */
		protected function get_query_results() {
			$transient_name = $this->get_transient_name();
			$cache          = wc_string_to_bool( $this->attributes['cache'] ) === true;
			$results        = $cache ? get_transient( $transient_name ) : false;

			if ( true|| false === $results ) {

				$query = new \WP_Query( $this->query_args );

				$paginated = ! $query->get( 'no_found_rows' );

				$results = (object) array(
					'ids'          => wp_parse_id_list( $query->ids ),
					'posts'        => $query->posts,
					'total'        => $paginated ? (int) $query->found_posts : count( $query->posts ),
					'total_pages'  => $paginated ? (int) $query->max_num_pages : 1,
					'per_page'     => (int) $query->get( 'posts_per_page' ),
					'current_page' => $paginated ? (int) max( 1, $query->get( 'paged', 1 ) ) : 1,
				);

				if ( $cache ) {
					set_transient( $transient_name, $results, DAY_IN_SECONDS * 30 );
				}
			}

			// Remove ordering query arguments which may have been added by get_catalog_ordering_args.
			WC()->query->remove_ordering_args();
			return $results;
		}

		/**
		 * Loop over found products.
		 *
		 * @since  3.2.0
		 * @return string
		 */
		protected function product_loop() {
			$columns  = absint( $this->attributes['columns'] );
			$classes  = $this->get_wrapper_classes( $columns );
			$products = $this->get_query_results();

			ob_start();

			if ( $products && $products->ids && $products->posts ) {
				// Prime caches to reduce future queries.
				if ( is_callable( '_prime_post_caches' ) ) {
					_prime_post_caches( $products->ids );
				}

				// Setup the loop.
				wc_setup_loop(
					array(
						'columns'      => $columns,
						'name'         => $this->type,
						'is_shortcode' => true,
						'is_search'    => false,
						'is_paginated' => wc_string_to_bool( $this->attributes['paginate'] ),
						'total'        => $products->total,
						'total_pages'  => $products->total_pages,
						'per_page'     => $products->per_page,
						'current_page' => $products->current_page,
					)
				);

				$original_post = $GLOBALS['post'];

				do_action( "woocommerce_shortcode_before_{$this->type}_loop", $this->attributes );

				// Fire standard shop loop hooks when paginating results so we can show result counts and so on.
				if ( wc_string_to_bool( $this->attributes['paginate'] ) ) {
					do_action( 'woocommerce_before_shop_loop' );
				}

				woocommerce_product_loop_start();

				if ( wc_get_loop_prop( 'total' ) ) {
					foreach ( $products->posts as $post ) {
						$GLOBALS['post'] = $post; // WPCS: override ok.
						setup_postdata( $GLOBALS['post'] );

						// Set custom product visibility when quering hidden products.
						add_action( 'woocommerce_product_is_visible', array( $this, 'set_product_as_visible' ) );

						// Render product template.
						wc_get_template_part( 'content', 'product' );

						// Restore product visibility.
						remove_action( 'woocommerce_product_is_visible', array( $this, 'set_product_as_visible' ) );
					}
				}

				$GLOBALS['post'] = $original_post; // WPCS: override ok.
				woocommerce_product_loop_end();

				// Fire standard shop loop hooks when paginating results so we can show result counts and so on.
				if ( wc_string_to_bool( $this->attributes['paginate'] ) ) {
					do_action( 'woocommerce_after_shop_loop' );
				}

				do_action( "woocommerce_shortcode_after_{$this->type}_loop", $this->attributes );

				wp_reset_postdata();
				wc_reset_loop();
			} else {
				do_action( "woocommerce_shortcode_{$this->type}_loop_no_results", $this->attributes );
			}

			return '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">' . ob_get_clean() . '</div>';
		}

	}

endif;