<?php
namespace LetsGoDev\Controllers;

use LetsGoDev\Classes\Logger;

/**
 * LicenseApi Class
 *
 * This class connect with LetsGoDev API
 *
 * @since      1.0.0
 * @package    LetsGoDev
 * @subpackage LetsGoDev/Controllers
 * @author     LetsGoDev <support@letsgodev.com>
 */

Class LicenseAPIController {

	/**
	 * Results for each API call
	 * @var array
	 */
	public static $result = [];

	/**
	 * Construct
	 * @param object $settings
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
	}


	/**
	 * Check if it has license and link it.
	 * @return boolean
	 */
	public function hasLicense() {

		$hasLicense	= \get_transient( $this->settings->slug . '_has_license' );
		$licenseKey = \get_option( $this->settings->slug . '_license', '' );

		// If empty license key
		if( empty( $licenseKey ) ) {
			\delete_transient( $this->settings->slug . '_has_license');
			\delete_transient( $this->settings->slug . '_license_expired' );
			return false;
		}

		// If the transient variable still recorded
		if( ! empty( $hasLicense ) ) {
			return true;
		}

		// Process Request
		$response = $this->processRequest( 'status-check', $licenseKey );

		// Process Response
		$isSuccess = $this->processResponse( $response, [ 's205', 's215' ], true );

		// If false, remove license
		if( ! $isSuccess ) {
			\delete_option( $this->settings->slug . '_license' );
			\delete_transient( $this->settings->slug . '_license_expired' );
		}

		return $isSuccess;
	}


	/**
	 * CheckLicense
	 * @return mixed
	 */
	public function checkLicense() {

		// Check License
		$licenseKey = \get_option( $this->settings->slug . '_license', '' );

		if( empty( $licenseKey ) ) {
			
			$result = [
				'error'	=> esc_html__( 'The license is missing', 'letsgodev' ),
				'data'	=> [
					'method'	=> 'checkLicense',
				]
			];

			// Last Result
			self::$result[ $this->settings->slug ] = $result;

			// Logger
			Logger::message( $result, $this->settings->slug );

			return false;
		}

		// Process Request
		$response = $this->processRequest( 'status-check', $licenseKey );

		// Process Response
		return $this->processResponse( $response, [ 's205', 's215', 's203' ] );	
	}


	/**
	 * Check is the license is active
	 * @return boolean
	 */
	public function isActive() {
		return $this->hasLicense() && ! get_transient( $this->settings->slug . '_license_expired' );
	}


	/**
	 * Check if the license expired
	 * @return boolean
	 */
	public function isExpired() {
		return get_transient( $this->settings->slug . '_license_expired' );
	}


	/**
	 * Get plugim imnformation
	 * @return array
	 */
	public function getInfo() {
		
		// Process the request to get info
		$response = $this->processRequest( 'plugin_information' );

		// is Down Server
		if( \wp_remote_retrieve_response_code( $response ) !== 200 ) {

			$result = [
				'error'	=> $response->get_error_message(),
				'data'	=> [
					'method'	=> 'getInfo',
				]
			];

			// Last Result
			self::$result[ $this->settings->slug ] = $result;

			// Logger
			Logger::message( $result, $this->settings->slug );

			return false;
		}

		self::$result[ $this->settings->slug ] = [
			'error'	=> '',
			'data'	=> [
				'body'	=> \wp_remote_retrieve_body( $response )
			]
		];

		return true;
	}


	/**
	 * Check Update
	 * @return mixed
	 */
	public function checkUpdate() {
		global $wp_version;

		// Get params
		if( $this->isActive() ) {
			$response = $this->processRequest( 'plugin_update' );
		} else {
			$response = $this->processRequest( 'code_version' );
		}

		// is Down Server
		if( \wp_remote_retrieve_response_code( $response ) !== 200 ) {

			$result = [
				'error'	=> $response->get_error_message(),
				'data'	=> [
					'method'	=> 'checkUpdate',
				]
			];

			// Last Result
			self::$result[ $this->settings->slug ] = $result;

			// Logger
			Logger::message( $result, $this->settings->slug );

			return false;
		}


		self::$result[ $this->settings->slug ] = [
			'error'	=> '',
			'data'	=> [
				'body'	=> \wp_remote_retrieve_body( $response )
			]
		];

		return true;
	}


	/**
	 * Activate License
	 * @param  string $licenseKey
	 * @return mixed
	 */
	public function activate( string $licenseKey ) {
		
		// Params
		$response = $this->processRequest( 'activate', $licenseKey );

		// Process the response
		$isSuccess = $this->processResponse( $response, [ 's100', 's101' ] );

		if( $isSuccess ) {
			update_option( $this->settings->slug . '_license', $licenseKey );
		}

		return $isSuccess;
	}


	/**
	 * Deactivate
	 * @return mixed
	 */
	public function deactivate() {
		// Check License
		$licenseKey = \get_option( $this->settings->slug . '_license', '' );

		if( empty( $licenseKey ) ) {

			$result = [
				'error'		=> \esc_html__( 'The license is missing', 'letsgodev' ),
				'data'		=> [
					'method'	=> 'deactivate',
				]
			];

			// Last Result
			self::$result[ $this->settings->slug ] = $result;

			// Logger
			Logger::message( $result, $this->settings->slug );

			return false;
		}

		// Process Request
		$response = $this->processRequest( 'deactivate', $licenseKey );

		// Process Response
		$isSuccess = $this->processResponse( $response, [ 's201' ] );

		if( $isSuccess ) {
			\delete_option( $this->settings->slug . '_license' );
			//delete_option( $this->settings->slug . '_license_dates' );
			\delete_transient( $this->settings->slug . '_has_license' );
			\delete_transient( $this->settings->slug . '_license_expired' );
		}

		return $isSuccess;
	}


	/**
	 * Prepare the URL
	 * @param  string $action
	 * @return array
	 */
	private function processRequest( string $action, string $licenseKey = '' ) {
		global $wp_version;

		// Get License
		if( empty( $licenseKey ) ) {
			$licenseKey = \get_option( $this->settings->slug . '_license', '' );
		}

		$params = [
			'woo_sl_action'     => $action,
			'version'           => $this->settings->version,
			'product_unique_id' => $this->settings->product,
			'licence_key'       => $licenseKey,
			'domain'            => $this->settings->domain,
			'wp-version'        => $wp_version
		];

		// URL
		$request_uri = $this->settings->api_url . '?' . http_build_query( $params , '', '&' );
		
		// Get response
		$response = \wp_remote_get( $request_uri, [
			'timeout'       => 20,
			'user-agent'    => 'WordPress/' . $wp_version . '; ' . \get_bloginfo( 'url' )
		] );

		return $response;
	}


	/**
	 * Process response
	 * @param  array        $response
	 * @param  array        $allowedCodes
	 * @param  bool|boolean $isDownServer
	 * @return array
	 */
	private function processResponse( array $response, array $allowedCodes = [], bool $isDownServer = false ) {

		// If server is down
		if( \wp_remote_retrieve_response_code( $response ) !== 200 ) {

			$result = [
				'error'		=> \esc_html__( 'Server is down right', 'letsgodev' ),
				'data'		=> [
					'method'	=> 'processResponse',
					'response'	=> $response->get_error_message(),
					'api_url'	=> $this->settings->api_url,
					'product'	=> $this->settings->product,
					'domain'	=> $this->settings->domain,
					'version'	=> $this->settings->version,
				]
			];

			// Last Result
			self::$result[ $this->settings->slug ] = $result;

			// Logger
			Logger::message( $result, $this->settings->slug );

			// If is down server, to convert to true
			if( $isDownServer ) {
				\set_transient(
					$this->settings->slug . '_has_license',
					1, WEEK_IN_SECONDS
				);

				return true;
			}

			return false;
		}

		// Receive Body
		$dataBody = json_decode( \wp_remote_retrieve_body( $response ) );

		// If is array
		$body = isset( $dataBody[0] ) ? $dataBody[0] : $dataBody;


		// If there is a problem establishing a connection
		if( empty( $body->status ) ) {

			$result = [
				'error'		=> \esc_html__( 'There was a problem establishing a connection to the API server', 'letsgodev' ),
				'data'		=> [
					'method'	=> 'processResponse',
					'api_url'	=> $this->settings->api_url,
					'product'	=> $this->settings->product,
					'domain'	=> $this->settings->domain,
					'version'	=> $this->settings->version,
					'body'		=> $body,
				]
			];

			// LastResult
			self::$result[ $this->settings->slug ] = $result;

			// Logger
			Logger::message( $result, $this->settings->slug );

			// IF success
			return false;
		}

		// If Error
		if( $body->status != 'success' ) {

			$result = [
				'error'	=> $body->message ?? '',
				'data'	=> [
					'method'	=> 'processResponse',
					'code'		=> $body->status_code ?? '',
					'body'		=> $body,
				]
			];
			
			// LastResult
			self::$result[ $this->settings->slug ] = $result;

			// Logger
			Logger::message( $result, $this->settings->slug );

			// IF success
			return false;

		} elseif( ! empty( $allowedCodes ) && ! in_array( $body->status_code, $allowedCodes ) ) {

			$result = [
				'error'		=> $body->message ?? '',
				'data'		=> [
					'method'	=> 'processResponse',
					'response'	=> \esc_html__( 'Status Code no allowed', 'letsgodev' ),
					'allowed'	=> $allowedCodes,
					'code'		=> $body->status_code ?? '',
					'body'		=> $body,
				]
			];

			// LastResult
			self::$result[ $this->settings->slug ] = $result;
			
			Logger::message( $result, $this->settings->slug );

			// IF success
			return false;
		}


		// set "has license"
		\set_transient( $this->settings->slug . '_has_license', 1, WEEK_IN_SECONDS );

		// Check license status
		if( $body->licence_status == 'active' ) {

			// If there is info about the license date expire
			if( isset( $body->licence_start ) && isset( $body->licence_expire ) ) {
				\update_option( $this->settings->slug . '_license_dates', [
					'start'		=> $body->licence_start,
					'expire'	=> $body->licence_expire,
				] );
			}

			// Remove license expired
			\delete_transient( $this->settings->slug . '_license_expired' );
		
		} else {
			\set_transient( $this->settings->slug . '_license_expired', 1, WEEK_IN_SECONDS );
		}

		
		$result = [
			'error'	=> '',
			'data'	=> [
				'method'	=> 'processResponse',
				'code'		=> $body->status_code,
				'start'		=> $body->licence_start ?? '',
				'expire'	=> $body->licence_expire ?? '',
			],
		];

		// LastResult
		self::$result[ $this->settings->slug ] = $result;
		
		return true;
	}


	/**
	 * Get Results from API Call
	 * @return array
	 */
	public function getLastResult( string $slug ) {
		return self::$result[ $slug ] ?? [];
	}
}