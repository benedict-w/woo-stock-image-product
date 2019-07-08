<?php

require_once __DIR__ . '/../../../wp-load.php';
require_once __DIR__ . '/inc/adobe-stock-api.php';

if ( $id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT ) ) {

	if ( $stock_image = \WooStockImageProduct\AdobeStockApi::get_image_by_id( $id ) ) {

		$img_info = getimagesize( $stock_image->thumbnail_url );

		if ( stripos( $img_info['mime'], 'image/' ) !== false ) {
			header( 'Content-type: ' . $img_info['mime'] );
			header('Pragma: public');
			header('Cache-Control: max-age=86400');
			header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
			readfile( $stock_image->thumbnail_url );
			exit;
		}

	}

}

// FAIL
global $wp_query;
$wp_query->set_404();
status_header( 404 );
get_template_part( 404 );
exit();