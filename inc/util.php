<?php
/**
 * Util
 *
 * Helper functions
 */

namespace WooStockImageProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly
}

/**
 * replace_img_attrs
 *
 * @param $html
 * @param \WC_Product_Stock_Image $stock_image_product
 *
 * @return string
 */
function replace_img_attrs ( $html, $stock_image_product ) {
	if ($stock_image = $stock_image_product->get_stock_image() ) {
		$html = replace_img_src( $html, $stock_image->thumbnail_url);
		$html = replace_img_srcset( $html, $stock_image->thumbnail_url);
		$html = replace_img_alt( $html, $stock_image->title);
	}

	return $html;
}

/**
 * replace_img_src
 *
 * @param $html
 * @param $src
 *
 * @return null|string|string[]
 */
function replace_img_src( string $html, string $src ) {
	return preg_replace( '@src="([^"]+)"@', "src=\"{$src}\"", $html );
}

/**
 * replace_img_alt
 *
 * TODO replace empty alt too
 *
 * @param $html
 * @param $alt
 *
 * @return null|string|string[]
 */
function replace_img_alt( string $html, string $alt ) {
	return preg_replace( '@alt="([^"]+)"@', "alt=\"{$alt}\"", $html );
}

/**
 * replace_img_srcset
 *
 * TODO sizes
 *
 * 66w, 100w, 150w, 500w
 *
 * sizes="(max-width: 500px) 100vw, 500px
 *
 *
 * @param $html
 * @param $src
 *
 * @return null|string|string[]
 */
function replace_img_srcset ( string $html, string $src ) {
	return preg_replace( '@srcset="([^"]+)"@', "srcset=\"{$src}\"", $html );
}

/**
 * format_stock_image_product_title
 *
 * @param $product_title
 * @param $stock_image_title
 *
 * @return string
 */
function format_stock_image_product_title( string $product_title, string $stock_image_title ) {
	return sprintf( "%s: %s", $product_title, $stock_image_title );
}

/**
 * DEBUG EMAIL
 *
 */

if ( get_site_url() === 'http://dev.artvue.co.uk' ) {

	add_filter( 'wp_mail', 'WooStockImageProduct\mail_filter' );

	function mail_filter( $args ) {

		$new_wp_mail = array(
			'to' => "benedict_wallis@yahoo.co.uk",
		);

		return $new_wp_mail;
	}
}

if ( get_site_url() === 'http://local.artvue.co.uk' ) {

	add_action( 'phpmailer_init', 'WooStockImageProduct\stop_mail' );

	function stop_mail( $phpmailer ) {
		$phpmailer->ClearAllRecipients();
	}
}