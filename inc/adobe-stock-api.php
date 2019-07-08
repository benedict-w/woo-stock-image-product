<?php

namespace WooStockImageProduct;

require_once __DIR__ . '/../vendor/autoload.php';

use \AdobeStock\Api\Client\AdobeStock;
use \AdobeStock\Api\Client\Http\HttpClient;
use AdobeStock\Api\Request\License;
use \AdobeStock\Api\Request\SearchFiles;
use \AdobeStock\Api\Request\License as LicenseRequest;
use \AdobeStock\Api\Core\Constants;
use \AdobeStock\Api\Models\SearchParameters;

use \GuzzleHttp\Client;

/**
 * Class AdobeStockApi
 *
 * See - https://github.com/adobe/stock-api-docs
 * and - https://github.com/adobe/stock-api-libphp
 *
 * @package WooStockImageProduct
 */
class AdobeStockApi {

	protected static $total_results = 0;
	protected static $results = array();
	protected static $adobe_client = null;

	/**
	 * AdobeStockApi constructor
	 *
	 * Singleton pattern for API access
	 *
	 */
	protected function __construct() {
	}

	/**
	 * get_results
	 *
	 * @return array
	 */
	public static function get_results() {
		return self::$results;
	}

	/**
	 * get_file_request
	 *
	 * @return SearchFiles
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	protected static function get_file_request() {
		$request = new SearchFiles();
		$request->setLocale( get_locale() );

		return $request;
	}

	/**
	 * get_search_request
	 *
	 * @param $keyword
	 * @param int $limit
	 *
	 * @return SearchFiles
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	protected static function get_search_request( $keyword, $limit = 10 ) {

		$request       = self::get_file_request();
		$search_params = new SearchParameters();
		$search_params->setWords( $keyword );
		$search_params->setLimit( $limit );

		$request->setSearchParams( $search_params );

		return $request;
	}

	/**
	 * get_license_request
	 *
	 * @return License
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	protected static function get_license_request( $image_id ) {
		$request = new LicenseRequest();
		$request->setLocale( get_locale() );
		$request->setContentId( intval($image_id) );
		$request->setLicenseState( 'STANDARD' );

		return $request;
	}

	/**
	 * get_stock_client
	 *
	 * @return AdobeStock
	 */
	protected static function get_stock_client() {

		if ( empty( self::$adobe_client ) ) {

			$http_client = new HttpClient();

			$api_key  = Settings::get_adobe_api_key();
			$app_name = Settings::get_adobe_app_name();

			self::$adobe_client = new AdobeStock( $api_key, $app_name, 'PROD', $http_client );
		}

		return self::$adobe_client;
	}

	/**
	 * search
	 *
	 * TODO sanitize $keyword - max size etc?
	 *
	 * @param $keyword
	 * @param int $limit
	 *
	 * @param $transient - expiration in seconds, set if wanting to persist the request (e.g. displaying archive)
	 *
	 * @throws \AdobeStock\Api\Exception\StockApi
	 *
	 * @return array $results
	 */
	public static function search( $keyword, $limit = 10, $page = 1, $transient = 0 ) {

		$key       = sanitize_key( sprintf( "stock_image_search_%s_%d_%d", $keyword, $page, $limit ) );
		$count_key = sanitize_key( sprintf( "stock_image_count_%s", $keyword ) );

		if ( $stock_images = wp_cache_get( $key ) ) {
			return $stock_images;
		}

		if ( $stock_images = get_transient( $key ) ) {
			// add to object cache first
			wp_cache_add( $key, $stock_images );

			return $stock_images;
		}

		// restrict response to required fields
		$results_columns     = Constants::getResultColumns();
		$result_column_array = array(
			$results_columns['NB_RESULTS'],
			$results_columns['TITLE'],
			$results_columns['ID'],
			$results_columns['THUMBNAIL_URL'],
		);

		$request = self::get_search_request( $keyword, $limit );

		$request->setResultColumns( $result_column_array );

		$client = self::get_stock_client();

		$response = $client->searchFilesInitialize( $request, '' )->getResponsePage( $page );

		self::$total_results = intval( $response->getNbResults() );
		self::$results       = $response->getFiles();

		// object cache results
		wp_cache_add( $key, self::$results );

		// object cache search count
		wp_cache_add( $count_key, self::$total_results );

		// store transients if required
		if ( $transient = intval( $transient ) ) {
			// persist only if we have results (in case of API errors)
			if ( count( self::$results ) ) {
				set_transient( $key, self::$results, $transient );
				set_transient( $count_key, self::$total_results, $transient );
			}
		}

		return self::$results;
	}

	/**
	 * get_results_count
	 *
	 * @param $keyword
	 *
	 * @return array|int
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	public static function get_results_count( $keyword, $transient = 0 ) {

		$key = sanitize_key( sprintf( "stock_image_count_%s", $keyword ) );

		if ( $search_count = wp_cache_get( $key ) ) {
			return $search_count;
		}

		if ( $search_count = get_transient( $key ) ) {
			// add to object cache first
			wp_cache_add( $key, $search_count );

			return $search_count;
		}

		$results_columns     = Constants::getResultColumns();
		$result_column_array = array(
			$results_columns['NB_RESULTS'],
		);

		$request = self::get_search_request( $keyword, 1 );

		$request->setResultColumns( $result_column_array );

		$client = self::get_stock_client();

		$response            = $client->searchFilesInitialize( $request, '' )->getResponsePage( 1 );
		self::$total_results = intval( $response->getNbResults() );

		// store transients if required
		if ( $transient = intval( $transient ) ) {
			// persist only if we have results (in case of API errors)
			if ( self::$total_results ) {
				set_transient( $key, self::$total_results, $transient );
			}
		}

		return self::$total_results;
	}

	/**
	 * get_image_by_id
	 *
	 * gets the stock image from the adobe api and add to caches
	 *
	 * @param $id
	 * @param $transient - expiration in seconds, set if wanting to persist the request (e.g. adding to cart, etc.)
	 *
	 * @return array
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	public static function get_image_by_id( $id, $transient = 0 ) {

		$key = sanitize_key( sprintf( "stock_image_%s", $id ) );

		if ( $stock_image = wp_cache_get( $key ) ) {
			return $stock_image;
		}

		if ( $stock_image = get_transient( $key ) ) {
			// add to object cache first
			wp_cache_add( $key, $stock_image );

			return $stock_image;
		}

		$results_columns     = Constants::getResultColumns();
		$result_column_array = array(
			$results_columns['TITLE'],
			$results_columns['ID'],
			$results_columns['THUMBNAIL_URL'],
			$results_columns['DETAILS_URL'],
		);

		$request = self::get_file_request();

		$search_params = new SearchParameters();
		$search_params->setMediaId( intval( $id ) );

		$request->setSearchParams( $search_params );

		$request->setResultColumns( $result_column_array );

		$client = self::get_stock_client();

		$response = $client->searchFilesInitialize( $request, '' )->getResponsePage( 0 );

		self::$results = $response->getFiles();

		if ( isset( self::$results[0] ) ) {

			$stock_image = self::$results[0];

			wp_cache_add( $key, $stock_image );

			if ( $transient = intval( $transient ) ) {
				set_transient( $key, $stock_image, $transient );
			}

			return $stock_image;
		}

		return null;
	}

	/**
	 * get_member_profile
	 *
	 * @param $image_id
	 *
	 * @return \AdobeStock\Api\Response\License
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	public static function get_member_profile( $image_id ) {
		$request = self::get_license_request( $image_id );
		$client  = self::get_stock_client();

		return $client->getMemberProfile($request, Settings::get_adobe_access_token() );
	}

	/**
	 * get_content_info
	 *
	 * @param $image_id
	 *
	 * @return \AdobeStock\Api\Response\License
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	public static function get_content_info( $image_id ) {
		$request = self::get_license_request( $image_id );
		$client  = self::get_stock_client();

		return $client->getContentInfo( $request, Settings::get_adobe_access_token() );
	}

	/**
	 * get_content_license
	 *
	 * @param $image_id
	 *
	 * @return \AdobeStock\Api\Response\License
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	public static function get_content_license( $image_id ) {

		// $member_response = self::get_member_profile( $image_id );

		$license_request = self::get_license_request( $image_id );

		/*
		$license_request->setLicenseReference( array(
			array(
				'id' => $image_id,
				'value'=> 'test',
			) )
		);
		*/

		$client = self::get_stock_client();

		return $client->getContentLicense( $license_request, Settings::get_adobe_access_token() );
	}

	/**
	 * get_download_asset_url
	 *
	 * @param $image_id
	 *
	 * @return string
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	public static function get_download_asset_url( $image_id ) {

		$request = self::get_license_request( $image_id );
		$client  = self::get_stock_client();

		return $client->downloadAssetUrl( $request, Settings::get_adobe_access_token() );

	}

	/**
	 * get_download_asset_stream
	 *
	 * @return string
	 * @throws \AdobeStock\Api\Exception\StockApi
	 */
	public static function get_download_asset_stream( $image_id ) {
		$request = self::get_license_request( $image_id );
		$client  = self::get_stock_client();

		return $client->downloadAssetStream( $request,  Settings::get_adobe_access_token() );
	}

	/**
	 * get_stock_filename
	 *
	 * From example code - https://github.com/adobe/stock-api-samples/blob/master/php/src/sdk_license1.php
	 *
	 * e.g. response-content-disposition=attachment%3B%20filename%3D%22AdobeStock_112670342.jpeg
	 *
	 * @param $url
	 * @param $path
	 *
	 * @return bool|string
	 */
	protected static function get_stock_filename( $url, $path ) {

		$query_re = '/filename.+?(AdobeStock_\d+\.\w+)(?:%|\'|")/';

		if ( preg_match( $query_re, $url, $matches ) && $matches[1] !== null ) {
			$name = $matches[1];
		} else {
			# strip leading slash if exists
			if ( strpos( $path, '/' ) === 0 ) {
				$name = substr( $path, 1 );
			} else {
				$name = $path;
			}
		};

		return $name;
	}

}