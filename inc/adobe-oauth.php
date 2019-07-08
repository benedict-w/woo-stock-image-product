<?php

namespace WooStockImageProduct;

use \GuzzleHttp\Client;

/**
 * Class AdobeOAuth
 *
 * https://www.adobe.io/apis/cloudplatform/console/authentication/gettingstarted.html
 *
 * @package WooStockImageProduct
 */
class AdobeOAuth {

	const ADOBE_AUTH_BASE_URL = 'https://ims-na1.adobelogin.com/ims';

	/**
	 * build_auth_url
	 *
	 * @param $client_id
	 * @param string $scope
	 * @param string $response_type
	 * @param string $locale
	 *
	 * @return string
	 */
	public static function build_auth_url( $client_id, $redirect_url, $scope = 'openid', $response_type = 'code', $locale = '' ) {
		return sprintf( "%s/authorize/?client_id=%s&redirect_url=%s&scope=%s&response_type=%s&locale=%s",
			self::ADOBE_AUTH_BASE_URL,
			$client_id,
			urlencode( $redirect_url ),
			$scope,
			$response_type,
			$locale ?: get_locale()
		);
	}

	/**
	 * send_auth_request
	 *
	 * TODO deprecate - use browser?
	 *
	 * @param $client_id
	 * @param $redirect_url
	 *
	 * @return bool
	 */
	public static function send_auth_request( $client_id, $client_secret, $redirect_url ) {
		$url    = self::build_auth_url( $client_id, $redirect_url );
		$client = new Client();

		$response_url = null;
		$response     = $client->get( $url, [
			'verify'   => false,
			'on_stats' => function ( \GuzzleHttp\TransferStats $stats ) use ( &$response_url ) {
				$response_url = $stats->getEffectiveUri();
			}
		] );

		$response->getBody()->getContents();

		$query = array();
		parse_str( $response_url->getQuery(), $query );

		if ( isset( $query['code'] ) ) {
			return self::generate_access_token( $query['code'], $client_id, $client_secret );
		}

		return false;
	}

	/**
	 * build_auth_url
	 *
	 * @param $client_id
	 * @param string $scope
	 * @param string $response_type
	 * @param string $locale
	 *
	 * @return string
	 */
	protected static function build_token_url( $code, $client_id, $client_secret, $grant_type = 'authorization_code' ) {
		return sprintf( "%s/token?code=%s&client_id=%s&client_secret=%s&grant_type=%s",
			self::ADOBE_AUTH_BASE_URL,
			$code,
			$client_id,
			$client_secret,
			$grant_type
		);
	}

	/**
	 * generate_access_token
	 *
	 * @param $code
	 * @param $client_id
	 * @param $client_secret
	 *
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 *
	 * @return bool
	 */
	public static function generate_access_token( $code, $client_id, $client_secret ) {
		$url    = self::build_token_url( $code, $client_id, $client_secret );
		$client = new Client();

		$response = $client->request( 'POST', $url, [ 'verify' => false ] );

		if ( is_object( $response ) ) {

			$response = json_decode( $response->getBody() );

			if ( $response->access_token ) {
				return update_option( 'adobe_api_access_token', $response->access_token );
				// TODO set reminder for token expiry?
			}
		}

		return false;

	}
}
