<?php
/**
 * License Class
 * @since  1.0.0
 */

if( ! class_exists( 'Letsgodev_License' ) ) {

final class Letsgodev_License {

	protected $slug;
	protected $plugin;
	protected $base;
	protected $name;
	
	private $error_title;
	private $error_desc;
	private $is_active = false;
	private $transient_hours = 24;

	/**
	 * License Construct
	 * @param array $data
	 */
	function __construct( $data = [] ) {

		if( ! isset( $data['plugin_name'] ) || ! isset( $data['plugin_base'] ) )
			throw new Exception( esc_html__( 'Wrong information', 'letsgodev' ) );

		$this->name 	= isset( $data['plugin_name'] ) ? esc_html( $data['plugin_name'] ) : '';
		$this->plugin 	= isset( $data['plugin_base'] ) ? esc_html( $data['plugin_base'] ) : '';
		$this->slug 	= dirname( $this->plugin );

		$this->base 	= isset( $data['library_base'] ) ? esc_html( $data['library_base'] ) : '';
		$this->dir 		= isset( $data['library_dir'] ) ? esc_url_raw( $data['library_dir'] ) : '';
		$this->url 		= isset( $data['library_url'] ) ? esc_url_raw( $data['library_url'] ) : '';

		$this->api_url 	= isset( $data['api_url'] ) ? esc_html( $data['api_url'] ) : '';
		$this->product 	= isset( $data['product'] ) ? esc_html( $data['product'] ) : '';
		$this->domain 	= isset( $data['domain'] ) ? esc_html( $data['domain'] ) : '';
		$this->version 	= isset( $data['version'] ) ? esc_html( $data['version'] ) : '';

		$this->email 	= isset( $data['email'] ) ? esc_html( $data['email'] ) : '';
		$this->redirect = isset( $data['redirect'] ) ? esc_html( $data['redirect'] ) : '';

		add_action( 'plugins_loaded', [ $this, 'load_plugin_textdomain' ] );
		add_filter( 'plugin_action_links_' . $this->plugin, [ $this, 'plugin_info' ], 11 );

		add_action( 'wp_ajax_' . $this->slug . '_get_status', [ $this, 'get_ajax_status' ] );
		add_action( 'wp_ajax_' . $this->slug . '_set_unassign', [ $this, 'set_ajax_unassign'] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts'] );
		add_action( 'admin_init', [ $this, 'verify' ] );

		add_action( 'after_setup_theme', [ $this, 'plugin_update' ] );
		add_action( 'admin_footer', [ $this, 'print_box' ], 1 );
	}


	/**
	 * Plugin Info
	 * @param  ARRAY $links
	 * @return ARRAY
	 */
	public function plugin_info( $links ) {

		$license = get_option( $this->slug . '_license', '' );

		if( ! isset( $license ) || empty( $license ) )
			return $links;

		$aux = $links['deactivate'];
		unset($links['deactivate']);
		
		$links['license'] = sprintf(
			'<a href="%s" class="letsgodev_license" data-slug="%s">%s</a>',
			'#open-license',
			$this->slug,
			esc_html__( 'Manager Licenses', 'letsgodev' )
		);

		$links['deactivate'] = $aux;

		return $links;
	}


	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Letsgodev_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'letsgodev',
			false,
			$this->base . '/languages/'
		);
	}


	/**
	 * Plugin Update
	 * @return mixed
	 */
	public function plugin_update() {
		
		// Take over the update check
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update'] );

		// Take over the Plugin info screen
		add_filter( 'plugins_api', [ $this, 'plugins_api_call' ], 10, 3);
	}


	/**
	 * Verify
	 * @return mixed
	 */
	public function verify() {

		$license = get_option( $this->slug . '_license', '' );

		// Activating
		if( isset( $_POST[$this->slug . '_license_key'] ) )
			$this->activate_license( $_POST[$this->slug . '_license_key'] );

		// If the license is no activated
		if( ! $this->is_active() ) {
			add_action( 'admin_notices', [ $this, 'get_license_html' ] );
		} elseif( get_transient( $this->slug . '_license_expired' ) ) {
			add_action( 'admin_notices', [ $this, 'expire_license_html' ] );
		}
	}

	/**
	 * verify if is active
	 * @return boolean
	 */
	public function is_active() {
		
		$is_active 		= get_transient( $this->slug . '_is_active_license' );
		$license_key 	= get_option( $this->slug . '_license', '' );

		if( ! isset( $license_key ) || empty( $license_key ) ) {
			delete_transient( $this->slug . '_is_active_license');
			return false;
		}

		if( isset( $is_active ) && $is_active == 1 )
			return true;

		$args = [
			'woo_sl_action'		=> 'status-check',
			'licence_key'		=> $license_key,
			'product_unique_id'	=> $this->product,
			'domain'			=> $this->domain,
		];
	
		$request_uri    = $this->api_url . '?' . http_build_query( $args );
		$data           = wp_remote_get( $request_uri );

		if( is_wp_error( $data ) || $data['response']['code'] != 200 ) {
			set_transient( $this->slug . '_is_active_license', 1, $this->transient_hours * HOUR_IN_SECONDS);
			return true;
		}

		$data_body = json_decode( $data['body'] );
		$body = isset( $data_body[0] ) ? $data_body[0] : $data_body;

		if( isset( $body->status ) ) {

			// Success codes
			$success = [ 's205', 's215' ];

			if( $body->status == 'success' && in_array( $body->status_code, $success ) ) {
				set_transient(
					$this->slug . '_is_active_license',
					1,
					$this->transient_hours * HOUR_IN_SECONDS
				);

				// If the license expired
				if( $body->licence_status == 'active' ) {
					delete_transient( $this->slug . '_license_expired' );
				} else {
					set_transient(
						$this->slug . '_license_expired',
						1,
						$this->transient_hours * HOUR_IN_SECONDS
					);
				}

				return true;
			}

			//[status] => success [status_code] => s203 [message] => Licence Is Inactive
			$array_error = [
				'datetime'	=> date('Y-m-d H:i:s'),
				'topic'		=> esc_html__( 'There was a problem checking the license', 'letsgodev' ),
				'status'	=> $body->status,
				'code'		=> $body->status_code,
				'message'	=> $body->message,
			];
			
			delete_option( $this->slug . '_license');
			update_option( $this->slug . '_is_active_error', $array_error );


			$this->error_title = esc_html__('There was a problem checking the license','letsgodev');
			$this->error_desc = print_r( $array_error, true );

			return false;	
		}

		$array_error = [
			'datetime'	=> date('Y-m-d H:i:s'),
			'topic'		=> esc_html__( 'There was a problem establishing a connection to the API server', 'letsgodev' ),
			'status'	=> '',
			'code'		=> '',
			'message'	=> '',
		];
		
		update_option( $this->slug .'_is_active_error', $array_error );

		$this->error_title = esc_html__( 'There was a problem establishing a connection to the API server', 'letsgodev' );
		$this->error_desc = '';

		return false;
	}


	/**
	 * Activate License
	 * @param  string $license_key
	 * @return mixed
	 */
	private function activate_license( $license_key = '' ) {

		$args = [
			'woo_sl_action'		=> 'activate',
			'licence_key'		=> $license_key,
			'product_unique_id'	=> $this->product,
			'domain'			=> $this->domain,
		];
		
		$request_uri    = $this->api_url . '?' . http_build_query( $args );
		$data           = wp_remote_get( $request_uri );

		// There was a problem establishing a connection to the API server
		if( is_wp_error( $data ) || $data['response']['code'] != 200 ) {

			$this->error_title = esc_html__( 'Server is down right. An email was sent to ' . $this->email, 'letsgodev' );
			$this->error_desc = print_r( $data, true );
			
			wp_mail( $this->email, sprintf( esc_html__( 'Letsgodev is down right (from %s) - Activating', 'letsgodev' ), site_url() ), print_r( $data ,true ) );
		
		} else {

			$data_body = json_decode( $data['body'] );

			// If is array
			$body = isset( $data_body[0] ) ? $data_body[0] : $data_body;
			
			if( isset( $body->status ) ) {

				// Success codes
				$success = [ 's100', 's101' ];
				
				if( $body->status == 'success' && in_array( $body->status_code, $success ) ) {

					// set transient
					set_transient(
						$this->slug . '_is_active_license',
						1,
						$this->transient_hours * HOUR_IN_SECONDS
					);

					// Check license status
					if( $body->licence_status == 'active' ) {
						// Remove license expired
						delete_transient( $this->slug . '_license_expired' );
					} else {
						set_transient(
							$this->slug . '_license_expired',
							1,
							$this->transient_hours * HOUR_IN_SECONDS
						);
					}

					// Save License
					update_option( $this->slug . '_license', $license_key );

					// If redirect
					if( isset( $this->redirect ) && ! empty( $this->redirect ) ) {
						set_transient( $this->slug . '_redirect', true, 30 );
						wp_safe_redirect( $this->redirect );
						exit;
					}

				} else {

					$error_data = [
						'datetime'	=> date('Y-m-d H:i:s'),
						'topic'		=> esc_html__( 'There was a problem activating the license', 'letsgodev' ),
						'status'	=> $body->status,
						'code'		=> $body->status_code,
						'message'	=> $body->message,
					];

					$this->error_title = esc_html__( 'There was a problem activating the license', 'letsgodev' );
					$this->error_desc = print_r( $error_data, true );

					// Save error info
					update_option( $this->slug . '_activate_error', $error_data );
				}
			} else {

				$error_data = [
					'datetime'	=> date('Y-m-d H:i:s'),
					'topic'		=> esc_html__( 'There was a problem establishing a connection to the API server', 'letsgodev' ),
					'status'	=> '',
					'code'		=> '',
					'message'	=> '',
				];

				$this->error_title = esc_html__( 'There was a problem establishing a connection to the API server', 'letsgodev' );
				$this->error_desc = '';

				// Save error info
				update_option( $this->slug . '_activate_error', $error_data );
			}
		}
	}


	/**
	 * Box on the top to enter the license
	 * @return mixed
	 */
	public function get_license_html() {

		$args = [
			'name' 			=> $this->name,
			'slug'			=> $this->slug,
			'is_error'		=> ! empty( $this->error_title ),
			'error_msg'		=> $this->error_title,
			'error_desc'	=> $this->error_desc,
		];

		extract( $args );

		include $this->dir . 'layouts/html-box-license.php';
	}


	/**
	 * Message when the license expired
	 * @return html
	 */
	public function expire_license_html() {
		$args = [
			'name' 			=> $this->name,
		];

		extract( $args );

		include $this->dir . 'layouts/html-expire-license.php';
	}


	/**
	 * Prepare the URL
	 * @param  string $action
	 * @param  array  $args
	 * @return array
	 */
	private function prepare_request( $action = '', $args = [] ) {

		global $wp_version;

		// Get License
		$license_key = get_option( $this->slug . '_license', '' );

		return [
			'woo_sl_action'		=> $action,
			'version'			=> $this->version,
			'product_unique_id'	=> $this->product,
			'licence_key'		=> $license_key,
			'domain'			=> $this->domain,
			'wp-version'		=> $wp_version
		];
	}


	/**
	 * Check if there is some update
	 * @param  OBJECT $checked_data
	 * @return OBJECT
	 */
	function check_update( $checked_data ) {
		
		if ( ! is_object( $checked_data ) || ! isset ( $checked_data->response ) )
			return $checked_data;

		// Check if there is an available update
		if( $this->is_active() && ! get_transient( $this->slug . '_license_expired' ) )
			$request_string = $this->prepare_request( 'plugin_update' );
		else
			$request_string = $this->prepare_request( 'code_version' );
		
		if( $request_string === FALSE )
			return $checked_data;

		global $wp_version;

		// Start checking for an update
		$request_uri = $this->api_url . '?' . http_build_query( $request_string , '', '&');

		$data = wp_remote_get( $request_uri, [
			'timeout'		=> 20,
			'user-agent'	=> 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
		] );

		if( is_wp_error( $data ) || $data['response']['code'] != 200 )
			return $checked_data;

		$response_block = json_decode( $data['body'] );
 
		if( ! is_array( $response_block ) || count( $response_block ) < 1 )
			return $checked_data;

		//retrieve the last message within the $response_block
		$response_block = $response_block[ count($response_block) - 1 ];

		$response = isset( $response_block->message ) ? $response_block->message : '';

		// Feed the update data into WP update
		if( is_object( $response ) && ! empty( $response ) ) {

			// New version
			if( ! isset( $response->new_version ) && isset( $response->version ) ) {

				// If it is no a new version
				if( version_compare( $response->version, $this->version, '<=' ) )
					return $checked_data;

				$response->new_version = $response->version;
			}

			//include slug and plugin data
			$response->slug = $this->slug;
			$response->plugin = $this->plugin;
			$response->url = $response->homepage;

			//if sections are being set
			if( isset( $response->sections ) )
				$response->sections = (array)$response->sections;

			//if banners are being set
			if( isset( $response->banners ) )
				$response->banners = (array)$response->banners;

			//if icons being set, convert to array
			if( isset( $response->icons ) )
				$response->icons = (array)$response->icons;

			$checked_data->response[$this->plugin] = $response;
		}

		return $checked_data;
	}


	/**
	 * Update process
	 * @param  [type] $def
	 * @param  string $action
	 * @param  [type] $args
	 * @return mixed
	 */
	public function plugins_api_call( $def, $action = '', $args ) {
		
		if ( ! is_object( $args ) || ! isset( $args->slug ) || $args->slug != $this->slug )
			return $def;

		$request_string = $this->prepare_request( $action, $args );
		
		if( $request_string === FALSE ) {
			return new WP_Error('plugins_api_failed', esc_html__( 'An error occour when try to identify the plugin.' , 'letsgodev') . '&lt;/p> &lt;p>&lt;a href=&quot;?&quot; onclick=&quot;document.location.reload(); return false;&quot;>'. esc_html__( 'Try again', 'letsgodev' ) .'&lt;/a>');
		}

		$request_uri = $this->api_url . '?' . http_build_query( $request_string , '', '&' );
		$data = wp_remote_get( $request_uri );

		if(is_wp_error( $data ) || $data['response']['code'] != 200 )
			return new WP_Error('plugins_api_failed', esc_html__('An Unexpected HTTP Error occurred during the API request.' , 'letsgodev') . '&lt;/p> &lt;p>&lt;a href=&quot;?&quot; onclick=&quot;document.location.reload(); return false;&quot;>'. esc_html__( 'Try again', 'letsgodev' ) .'&lt;/a>', $data->get_error_message());

		$response_block = json_decode( $data['body'] );
		//retrieve the last message within the $response_block
		$response_block = $response_block[ count($response_block) - 1 ];
		$response = $response_block->message;

		// Feed the update data into WP updater
		if( is_object( $response ) && ! empty( $response ) ) {
			
			//include slug and plugin data
			$response->slug = $this->slug;
			$response->plugin = $this->plugin;

			// New version
			if( ! isset( $response->new_version ) && isset( $response->version ) )
				$response->new_version = $response->version;

			//if sections are being set
			if( isset( $response->sections ) )
				$response->sections = (array)$response->sections;

			//if banners are being set
			if( isset( $response->banners ) )
				$response->banners = (array)$response->banners;

			//if icons being set, convert to array
			if( isset( $response->icons ) )
				$response->icons = (array)$response->icons;

			return $response;
		}
	}

	/**
	 * Verify the license status
	 * @return mixed
	 */
	public function get_ajax_status() {

		if ( ! wp_verify_nonce( $_POST[ 'wpnonce' ], 'letsgodev-wpnonce' ) )
        	die ( 'Busted!');
		
		$license_key = get_option( $this->slug . '_license', '' );

		if( ! isset( $license_key ) || empty( $license_key ) ) {
			$return = [
				'status'	=> 'error',
				'class'		=> 'error',
				'message'	=> esc_html__( 'The license is missing', 'letsgodev' ),
			];
		
		} else {

			$args = [
				'woo_sl_action'		=> 'status-check',
				'licence_key'		=> $license_key,
				'product_unique_id'	=> $this->product,
				'domain'			=> $this->domain,
			];
		
			$request_uri    = $this->api_url . '?' . http_build_query( $args );
			$data           = wp_remote_get( $request_uri );

			if( is_wp_error( $data ) || $data['response']['code'] != 200 ) {
				
				$result = [
					'isactive'	=> 0,
					'status'	=> 'error',
					'message'	=> esc_html__('Letsgodev is down right', 'letsgodev'),
				];

			} else {

				$data_body = json_decode( $data['body'] );

				$body = isset( $data_body[0] ) ? $data_body[0] : $data_body;

				if( isset( $body->status ) ) {

					if( $body->status == 'success' ) {

						switch( $body->status_code ) {
							
							case 's205' :
							case 's215' :
								$is_active = 1;
								$class = 'success';
								$message = esc_html__( 'Licence key is active to this domain', 'letsgodev' );
								break;
							
							case 's203' :
								$is_active = 0;
								$class = 'warning';
								$message = esc_html__( 'Licence key is unassigned', 'letsgodev' );
								break;

							default:
								$is_active = 0;
								$class = 'error';
								$message = esc_html__( 'Licence key is missing', 'letsgodev' );
						}

						$return = [
							'isactive'	=> $is_active,
							'status'	=> 'success',
							'class'		=> $class,
							'message'	=> $message,
						];

					} else {

						$return = [
							'isactive'	=> 0,
							'status'	=> 'error',
							'class'		=> 'error',
							'message'	=> esc_html__( 'There was a problem checking the license. This license does not correspond to this website.', 'letsgodev' ),
						];

						wp_send_json_error( $return );
					}

				} else {

					$return = [
						'isactive'	=> 0,
						'status'  	=> 'error',
						'class'		=> 'error',
						'message'	=> esc_html__( 'There was a problem establishing a connection to the API server', 'letsgodev' ),
					];
				}
			}
		}

		// Output
		if( isset( $return['status'] ) && $return['status'] == 'success' )
			wp_send_json_success( $return );
		else
			wp_send_json_error( $return );
	}

	/**
	 * Unassign the license from current domain
	 * @return mixed
	 */
	public function set_ajax_unassign() {
		if ( ! wp_verify_nonce( $_POST[ 'wpnonce' ], 'letsgodev-wpnonce' ) )
        	die( 'Busted!');
		
		$license_key = get_option( $this->slug . '_license', '');

		if( ! isset( $license_key ) || empty( $license_key ) ) {
			$return = [
				'status'	=> 'error',
				'message'	=> esc_html__( 'The license is missing', 'letsgodev' ),
			];
		
		} else {

			$args = [
				'woo_sl_action'		=> 'deactivate',
				'licence_key'		=> $license_key,
				'product_unique_id'	=> $this->product,
				'domain'			=> $this->domain,
			];
		
			$request_uri    = $this->api_url . '?' . http_build_query( $args );
			$data           = wp_remote_get( $request_uri );

			if( is_wp_error( $data ) || $data['response']['code'] != 200 ) {
				
				$result = [
					'status'	=> 'error',
					'class'		=> 'error',
					'message'	=> esc_html__( 'Letsgodev is down right', 'letsgodev' )
				];
			} else {

				$data_body = json_decode( $data['body'] );
				$body = isset( $data_body[0] ) ? $data_body[0] : $data_body;

				if( isset( $body->status ) ) {

					if( $body->status == 'success' ) {

						switch( $body->status_code ) {
							case 's201' :
								$message = esc_html__( 'Licence key successfully unassigned', 'letsgodev' );
								break;
							default:
								$message = esc_html__( 'Licence key is missing', 'letsgodev' );
						}

						$return = [
							'status'	=> 'success',
							'class'		=> 'success',
							'message'	=> $message
						];

						delete_option( $this->slug . '_license' );
						delete_transient( $this->slug . '_is_active_license' );
						delete_transient( $this->slug . '_license_expired' );
					
					} else {

						$return = [
							'status'	=> 'error',
							'class'		=> 'error',
							'message'	=> esc_html__( 'There was a problem checking the license', 'letsgodev' ),
						];
					}

				} else {

					$return = [
						'status'  	=> 'error',
						'class'		=> 'error',
						'message'	=> esc_html__( 'There was a problem establishing a connection to the API server', 'letsgodev' ),
					];
				}
			}
		}

		// Output
		if( isset( $return['status'] ) && $return['status'] == 'success' )
			wp_send_json_success( $return );
		else
			wp_send_json_error( $return );
	}

	/**
	 * Scripts on the admin
	 * @return mixed
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( ! is_admin() || ! isset( $screen->base ) || 'plugins' != $screen->base )
			return;

		wp_register_script(
			'letsgodev-license-js',
			$this->url . 'assets/js/license.js',
			[ 'jquery' ], false, true
		);

		wp_register_style(
			'letsgodev-license-css',
			$this->url . 'assets/css/license.css'
		);

		wp_enqueue_script( 'letsgodev-license-js' );
		wp_enqueue_style( 'letsgodev-license-css' );

		$loading_icon = admin_url( 'images/spinner-2x.gif' );

		// We verify if it is a redirect from activate license method
		$refresh = get_transient( $this->slug . '_redirect' );

		if( $refresh )
			delete_transient( $this->slug . '_redirect' );

		$array_license = [
			'loading_html'	=> sprintf('<img src="%s" alt="loading" />', $loading_icon),
			'ajax_url'		=> admin_url( 'admin-ajax.php' ),
			'wpnonce'		=> wp_create_nonce( 'letsgodev-wpnonce' ),
			'unassign_text'	=> esc_html__( 'Unassign from this website', 'letsgodev' ),
			'refresh'		=> $refresh,
		];

		wp_localize_script( 'letsgodev-license-js', 'letsgo', $array_license );
	}

	/**
	 * Print Box
	 * @return mixed
	 */
	public function print_box() {
		$screen = get_current_screen();

		if( ! is_admin() || ! isset( $screen->base ) || 'plugins' != $screen->base )
			return;

		include_once $this->dir . 'layouts/html-popup-license.php';
	}
}
}
?>