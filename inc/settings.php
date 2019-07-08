<?php

namespace WooStockImageProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly
}

if ( ! class_exists( 'WooStockImageProduct\Settings' ) ) :

	class Settings {

		/**
		 * Settings constructor.
		 */
		public static function init() {
			add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
			add_action( 'woocommerce_settings_stock_images', __CLASS__ . '::settings_tab' );
			add_action( 'woocommerce_update_options_stock_images', __CLASS__ . '::update_settings' );

			add_action( 'woocommerce_admin_field_adobe_auth_button', __CLASS__ . '::add_adobe_oauth_button' );
		}

		/**
		 * Add a new settings tab to the WooCommerce settings tabs array.
		 *
		 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
		 *
		 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
		 */
		public static function add_settings_tab( $settings_tabs ) {
			$settings_tabs['stock_images'] = __( 'Stock Images', 'woocommerce-settings-tab-stock-images' );

			return $settings_tabs;
		}

		/**
		 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
		 *
		 * @see https://docs.woocommerce.com/wc-apidocs/function-woocommerce_admin_fields.html
		 *
		 * @uses woocommerce_admin_fields()
		 * @uses self::get_settings()
		 */
		public static function settings_tab() {
			woocommerce_admin_fields( self::get_settings() );
		}

		/**
		 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
		 *
		 * @see https://docs.woocommerce.com/wc-apidocs/function-woocommerce_update_options.html
		 *
		 * @uses woocommerce_update_options()
		 * @uses self::get_settings()
		 */
		public static function update_settings() {
			woocommerce_update_options( self::get_settings() );
		}

		/**
		 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
		 *
		 * @see https://docs.woocommerce.com/document/settings-api/
		 *
		 * @return array Array of settings for @see woocommerce_admin_fields() function.
		 */
		public static function get_settings() {
			$settings = array(
				'section_title'                => array(
					'name' => __( "Adobe API Settings", PLUGIN_NAME ),
					'type' => 'title',
					'id'   => 'wc_settings_stock_images_section_title'
				),
				'_save_image_to_media_library' => array(
					'name' => __( "Save Images?", PLUGIN_NAME ),
					'type' => 'checkbox',
					'desc' => __( "Do you wish to save the purchased images to the media library?", PLUGIN_NAME ),
					'desc_tip' => __( "If not you will need to download from Adobe manually!", PLUGIN_NAME ),
					'id'   => 'wc_settings_save_stock_image_to_media_library'
				),
				'_adobe_app_name'              => array(
					'name'     => __( "Adobe App Name", PLUGIN_NAME ),
					'type'     => 'text',
					'desc'     => __( "Enter your Adobe App Name", PLUGIN_NAME ),
					'desc_tip' => __( "Enter your Adobe App Name", PLUGIN_NAME ),
					'id'       => 'wc_settings_stock_images_adobe_app_name'
				),
				'_adobe_api_key'               => array(
					'name'     => __( "Adobe API Key", PLUGIN_NAME ),
					'type'     => 'text',
					'desc'     => __( "Enter your Adobe API Key", PLUGIN_NAME ),
					'desc_tip' => __( "Enter your Adobe API Key", PLUGIN_NAME ),
					'id'       => 'wc_settings_stock_images_adobe_api_key'
				),
				'_adobe_api_secret'            => array(
					'name'     => __( "Adobe API Secret", PLUGIN_NAME ),
					'type'     => 'text',
					'desc'     => __( "Enter your Adobe API Secret", PLUGIN_NAME ),
					'desc_tip' => __( "Enter your Adobe API Secret", PLUGIN_NAME ),
					'id'       => 'wc_settings_stock_images_adobe_api_secret'
				),
				'_adobe_auth_button'           => array(
					'name' => __( 'Adobe Auth Button', PLUGIN_NAME ),
					'type' => 'adobe_auth_button',
					'id'   => 'wc_settings_adobe_auth_button'
				),
				'section_end'                  => array(
					'type' => 'sectionend',
					'id'   => 'wc_settings_stock_images_section_end'
				)
			);

			return apply_filters( 'wc_settings_tab_stock_image_settings', $settings );
		}

		/**
		 * add_adobe_oauth_button
		 *
		 */
		public static function add_adobe_oauth_button() {

			$api_key    = self::get_adobe_api_key();
			$api_secret = self::get_adobe_api_secret();

			$success = false;
			$error   = null;

			if ( $access_code = filter_input( INPUT_GET, 'code', FILTER_SANITIZE_STRING ) ) {

				if ( $api_key && $api_secret ) {

					try {

						$success = AdobeOAuth::generate_access_token( $access_code, $api_key, $api_secret );

					} catch ( \GuzzleHttp\Exception\ClientException $e ) {

						$success = false;

						$error = sprintf( __( "Error response: %s", PLUGIN_NAME ), esc_html( $e->getResponse()->getStatusCode() ) );

						$body = json_decode( $e->getResponse()->getBody() );
						if ( ! empty( $body->error ) ) {
							$error .= '<br>';
							$error .= esc_html( $body->error );
						}
					}

				}

			}

			?>
            <tr valign="top">
                <th scope="row"></th>
                <td class="forminp">
					<?php if ( $api_key ) : ?>
                        <a href="<?php echo AdobeOAuth::build_auth_url( self::get_adobe_api_key(), admin_url( 'admin.php?page=wc-settings&tab=stock_images' ) ) ?>"
                           style="display: inline-block; margin-bottom: 15px;"><?php _e( "Authorize Adobe Account" ) ?></a>
					<?php endif; ?>
					<?php if ( $access_code ) : ?>
						<?php if ( ! $api_key ) : ?>
                            <p style="color: #dc3232;"><?php _e( "Authorization requires a valid API Key", PLUGIN_NAME ); ?></p>
						<?php endif ?>
						<?php if ( ! $api_secret ) : ?>
                            <p style="color: #dc3232;"><?php _e( "Authorization requires a valid API Secret", PLUGIN_NAME ); ?></p>
						<?php endif ?>
						<?php if ( $api_secret && $api_key ) : ?>
							<?php if ( $success ) : ?>
                                <p>
                                    <strong style="color: #46b450;"><?php _e( "Authorization successful!", PLUGIN_NAME ); ?></strong>
                                </p>
							<?php else : ?>
                                <p>
                                    <strong style="color: #dc3232;"><?php _e( "Authorization failed!!", PLUGIN_NAME ); ?></strong>
                                </p>
								<?php if ( $error ) : ?>
                                    <p style="color: #dc3232;"><?php echo $error; ?></p>
								<?php endif ?>
							<?php endif ?>
						<?php endif ?>
					<?php endif ?>
                </td>

            </tr>
			<?php

		}

		/**
		 * get_adobe_api_name
		 *
		 * @return string
		 */
		public static function get_adobe_app_name() {
			return get_option( 'wc_settings_stock_images_adobe_app_name' );
		}

		/**
		 * get_adobe_api_key
		 *
		 * @return string
		 */
		public static function get_adobe_api_key() {
			return get_option( 'wc_settings_stock_images_adobe_api_key' );
		}

		/**
		 * get_adobe_api_secret
		 *
		 * @return string
		 */
		public static function get_adobe_api_secret() {
			return get_option( 'wc_settings_stock_images_adobe_api_secret' );
		}

		/**
		 * get_adobe_access_token
		 *
		 * @return string
		 */
		public static function get_adobe_access_token() {
			return get_option( 'adobe_api_access_token' );
		}

		/**
		 * get_save_image_to_media_library
		 *
		 * @return string
		 */
		public static function get_save_image_to_media_library() {
			return get_option( 'wc_settings_save_stock_image_to_media_library' );
		}
	}

	Settings::init();

endif;