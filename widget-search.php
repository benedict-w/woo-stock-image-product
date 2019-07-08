<?php
/**
 * WordPress WooStockImageProduct Widget
 *
 */

namespace WooStockImageProduct;

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SearchWidget extends \WP_Widget {

	/**
	 * widget_slug
	 *
	 * @var      string
	 */
	protected $widget_slug = 'woo-stock-product-image-search-widget';
	protected $widget_name = '';

	/**
	 * Specifies the classname and description, instantiates the widget,
	 * loads localization files, and includes necessary stylesheets and JavaScript.
	 */
	public function __construct() {

		$this->widget_name = __( "Product Image Search", PLUGIN_NAME );

		parent::__construct(
			$this->widget_slug,
			$this->widget_name,
			array(
				'classname'   => "widget_product_search {$this->widget_slug}",
				'description' => __( "A widget to display the Stock Image search area.", PLUGIN_NAME )
			)
		);

		add_action( 'widgets_init', array( $this, 'register_widget' ) );
	}

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array args  The array of form elements
	 * @param array instance The current instance of the widget
	 */
	public function widget( $args, $instance ) {

		if ( ! isset ( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		extract( $args, EXTR_SKIP );

		echo $before_widget;

		ob_start();
		include( plugin_dir_path( __FILE__ ) . 'views/search.php' );
		echo ob_get_clean();

		echo $after_widget;
	}


	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param array new_instance The new instance of values to be generated via the update.
	 * @param array old_instance The previous instance of values before the update.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		// TODO: update old values with the new, incoming values

		return $instance;

	}

	/**
	 * Generates the administration form for the widget.
	 *
	 * @param array instance The array of keys and values for the widget.
	 */
	public function form( $instance ) {

		// TODO: Define default values
		$instance = wp_parse_args(
			(array) $instance
		);

		// TODO: Store the values of the widget in their own variable

		// Display the admin form
		include( plugin_dir_path( __FILE__ ) . 'views/admin.widget.php' );

	}

	/**
	 * register_widget
	 *
	 */
	public function register_widget() {
		register_widget('WooStockImageProduct\SearchWidget');
	}

}