<?php
namespace LetsGoDev\Controllers;

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
			return false;
		}

		// If the transient variable still recorded
		if( ! empty( $hasLicense ) ) {
			return true;
		}

		// Process Request
		$response = $this->processRequest( 'status-check', $licenseKey );

		// Process Response
		$result = $this->processResponse( $response, [ 's205', 's215' ], true );

		
		// If false, remove license
		if( ! $result['success'] ) {
			delete_option( $this->settings->slug . '_license' );
		}

		return $result['success'];
	}


	public function checkLicense() {

		// Check License
		$licenseKey = \get_option( $this->settings->slug . '_license', '' );

		if( empty( $licenseKey ) ) {
			return [
				'success' 	=> false,
				'data'		=> [ 'error' => esc_html__( 'The license is missing', 'letsgodev' ) ],
			];
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
			return [
				'success' 	=> false,
				'data' 		=> [ 'error' => $response->get_error_message() ]
			];
		}

		return [
			'success' 	=> true,
			'data' 		=> [ 'body' => \wp_remote_retrieve_body( $response ) ]
		];
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
			return [
				'success' 	=> false,
				'data' 		=> [ 'error' => $response->get_error_message() ]
			];
		}

		return [
			'success' 	=> true,
			'data' 		=> [ 'body' => \wp_remote_retrieve_body( $response ) ]
		];
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
		$result = $this->processResponse( $response, [ 's100', 's101' ] );

		if( $result['success'] ) {
			update_option( $this->settings->slug . '_license', $licenseKey );
		}

		return $result;
	}



	public function deactivate() {
		// Check License
		$licenseKey = \get_option( $this->settings->slug . '_license', '' );

		if( empty( $licenseKey ) ) {
			return [
				'success' 	=> false,
				'data'		=> [ 'error' => esc_html__( 'The license is missing', 'letsgodev' ) ],
			];
		}

		// Process Request
		$response = $this->processRequest( 'deactivate', $licenseKey );

		// Process Response
		$result = $this->processResponse( $response, [ 's201' ] );

		if( $result['success'] ) {
			delete_option( $this->settings->slug . '_license' );
			delete_option( $this->settings->slug . '_license_dates' );
			delete_transient( $this->settings->slug . '_has_license' );
			delete_transient( $this->settings->slug . '_license_expired' );
		}

		return $result;
	}


	/**
	 * Prepare the URL
	 * @param  string $action
	 * @return array
	 */
	private function processRequest( string $action, string $licenseKey ) {
		global $wp_version;

		// Get License
		if( empty( $licenseKey ) ) {
			$licenseKey = get_option( $this->settings->slug . '_license', '' );
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

			// Logger
			$message = \esc_html__( 'Server is down right', 'letsgodev' );
			$message.= "\n" . print_r( $response->get_error_message(), true );

			Logger::message( $message );

			// If is down server, to convert to true
			if( $isDownServer ) {
				\set_transient(
					$this->settings->slug . '_has_license',
					1, WEEK_IN_SECONDS
				);

				return [
					'success'   => true,
					'data'      => [],
				];
			}

			return [
				'success'   => false,
				'data'      => [ 'error' => $response->get_error_message() ],
			];
		}

		// Receive Body
		$dataBody = json_decode( \wp_remote_retrieve_body( $response ) );

		// If is array
		$body = isset( $dataBody[0] ) ? $dataBody[0] : $dataBody;


		// If there is a problem establishing a connection
		if( empty( $body->status ) || $body->status != 'success' ) {

			$message = \esc_html__( 'There was a problem establishing a connection to the API server', 'letsgodev' );

			Logger::message( $message );

			// IF success
			return [
				'success'   => false,
				'data'      => [ 'error' => $message ],
			];
		}

		// If there is no a status code
		if( ! empty( $allowedCodes ) && ! in_array( $body->status_code, $allowedCodes ) ) {

			$message = \esc_html__( 'Status Code no allowed', 'letsgodev' );
			Logger::message( $message );

			// IF success
			return [
				'success'   => false,
				'data'      => [ 'error' => $message ],
			];
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

		// Save License
		//update_option( $this->settings->slug . '_license', $license_key );

		// If redirect
		//if( isset( $this->redirect ) && ! empty( $this->redirect ) ) {
		//	set_transient( $this->slug . '_redirect', true, 30 );
		//	wp_safe_redirect( $this->redirect );
		//	exit;
		//}

		
		return [
			'success' 	=> true,
			'data'		=> [ 'body' => $body, 'code' => $body->status_code ]
		];
	}
}