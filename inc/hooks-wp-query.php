<?php

/**
 * Query
 *
 * Code that hooks ino the WP_QUERY to customize search results fo stock_image_products
 *
 */

namespace WooStockImageProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly
}

require_once( 'adobe-stock-api.php' );

if ( ! class_exists( 'WooStockImageProduct\Query' ) ) :

	/**
	 * WooStockImageProduct\Query
	 *
	 * @class WooStockImageProduct\Query
	 *
	 */
	class Query {

		protected $no_posts = 0;
		protected $page = 1;

		/**
		 * constructor
		 *
		 */
		public function __construct() {

			if ( ! is_admin() ) {

				add_filter( 'pre_get_posts', array( $this, 'filter_product_type' ) );
				add_filter( 'the_posts', array( $this, 'add_stock_image_products' ), 10, 2 );
				add_filter( 'posts_search', array( $this, 'remove_search_term' ), 10, 2 );

				add_filter( 'pre_get_posts', array( $this, 'reset_wp_pagination' ) );

				add_filter( 'query_vars', array( $this, 'add_stock_image_product_query_vars' ) );

				add_filter( 'woocommerce_before_shop_loop', array( $this, 'modify_pagination' ), 20 );

			}
		}

		/**
		 * add_stock_image_product_query_vars
		 *
		 * @param $query_vars
		 *
		 * @return array
		 */
		public function add_stock_image_product_query_vars( $query_vars ) {
			$query_vars[] = 'product_type';
			$query_vars[] = 'image_id';

			return $query_vars;
		}

		/**
		 * is_stock_image_query
		 *
		 * Check $_GET resuest for product_type
		 *
		 * @return bool
		 */
		public static function is_stock_image_query( $query ) {

			return
				( $query->is_search || $query->is_archive ) &&
				( isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] === 'product' ) &&
				( isset( $query->query_vars['product_type'] ) && $query->query_vars['product_type'] === 'stock_image_product' );

		}

		/**
		 * filter_product_type
		 *
		 * Filter the returned posts on product_type taxonomy if stock_image_product search
		 *
		 * @hook pre_get_posts
		 *
		 * @return string
		 */
		public function filter_product_type( $query ) {

			if ( self::is_stock_image_query( $query ) ) {

				$query->set( 'post_type', 'product' );

				$query->set( 'tax_query', array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => 'stock_image_product',
					)
				) );

			}

			return $query;

		}

		/**
		 * remove_search_term
		 *
		 * If the query is a search and the product type is a stock_image_product, we want to use
		 * the search query var $_GET['s'] only for searching the Stock API and not for products, so directly
		 * remove if from the SQL WHERE clause.
		 *
		 * @hook posts_search
		 *
		 * @param $where
		 * @param $query
		 *
		 * @return string $where
		 */
		function remove_search_term( $where, $query ) {

			if ( self::is_stock_image_query( $query ) ) {
				$where = '';
			}

			return $where;
		}

		/**
		 * reset_wp_pagination
		 *
		 * @hook pre_get_posts
		 *
		 * @param $query
		 */
		public function reset_wp_pagination( $query ) {

			if ( self::is_stock_image_query( $query ) ) {

				if ( $s = filter_var( $query->query_vars['s'], FILTER_SANITIZE_STRING ) ) {
					try {
						if ( $total = AdobeStockApi::get_results_count( $s ) ) {

							// store for later
							$this->page = isset( $query->query_vars['paged'] ) && $query->query_vars['paged'] > 0
								? $query->query_vars['paged']
								: 1;

							$paged = ceil( $query->paged / $total );
							$query->set( 'paged', $paged );
						}
					} catch ( \Exception $ex ) {
						// TODO show error message
						$query->set( 'paged', 0 );
					}
				}

			}
		}

		/**
		 * add_stock_image_products
		 *
		 * Perform the stock image search and pad the posts array with products
		 *
		 * @hook the_posts
		 *
		 * @param $product
		 * @param $query
		 */
		public function add_stock_image_products( $posts, $query ) {

			if ( self::is_stock_image_query( $query ) ) {

				$image_products = array();

				$this->no_posts = count( $posts );

				if ( $s = filter_var( $query->query_vars['s'], FILTER_SANITIZE_STRING ) ) {

					try {

						$total_search_results = AdobeStockApi::get_results_count( $s );

						if ( $this->no_posts && $total_search_results ) {

							$posts_per_page = intval( get_option( 'posts_per_page' ) );

							// posts page index = sum of previous pages / total images tells us the product we are on
							$posts_index = floor( ( $this->page - 1 ) * $posts_per_page / $total_search_results );

							$images = array();

							// set up the query page results (used by archive shortcode)
							$total                = $total_search_results * $this->no_posts;
							$query->found_posts   = $total;
							$query->max_num_pages = ceil( $total / $posts_per_page );

							foreach ( $posts as $i => $post ) {

								if ( $i < $posts_index ) {
									continue; // we have already paginated this product
								}

								// images page index = modulo of all images fetched and total images / posts_per_page
								$images_index = ceil( ( ( ( ( $this->page - 1 ) * $posts_per_page ) + count( $images ) ) % $total_search_results ) / $posts_per_page );

								$images = AdobeStockApi::search( $s, $posts_per_page, $images_index );

								foreach ( $images as &$image ) {

									// need a new post object to work with
									$new_post = new \WP_Post( $post );

									// save the stock image to the post object (later added to the wc_product)
									$new_post->stock_image = $image;
									// set the post title
									$new_post->post_title = sprintf( "%s: %s", $post->post_title, $image->title );

									if ( count( $image_products ) < $posts_per_page ) {

										$image_products[] = $new_post;

									} else {

										// we have everything so return
										return $image_products;
									}
								}
							}
						}
					}
					catch ( \Exception $e ) {

						// TODO just catch API exceptions?
						// TODO show notice
						return array();

					}
				}

				return $image_products;
			}

			return $posts;
		}

		/**
		 * modify_pagination
		 *
		 * @param $pagination
		 *
		 * @return void
		 */
		public function modify_pagination() {

			global $wp_query;

			if ( self::is_stock_image_query( $wp_query ) ) {
				try {
					$s = filter_var( $wp_query->query_vars['s'], FILTER_SANITIZE_STRING);
					$total = AdobeStockApi::get_results_count( $s ) * $this->no_posts;
				} catch (\Exception $ex) {
					// TODO
					$total = 0;
				}

				$GLOBALS['woocommerce_loop']['total']        = $total;
				$GLOBALS['woocommerce_loop']['total_pages']  = ceil( $total / intval( get_option( 'posts_per_page' ) ) );
				$GLOBALS['woocommerce_loop']['current_page'] = $this->page;
			}
		}


	}

endif;

new Query();