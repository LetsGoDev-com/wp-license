<?php

namespace LetsGoDev\Modules;

use LetsGoDev\Classes\Module;
use LetsGoDev\Classes\Logger;

/**
 * Popup/Modal to configure license
 *
 *
 * @since      1.0.0
 * @package    LetsGoDev
 * @subpackage LetsGoDev/Modules
 * @author     LetsGoDev <support@letsgodev.com>
 */
class Popup extends Module {
 
	
	/**
	 * Init Hooks
	 * @return mixed
	 */
	public function iniHooks() {
		
		// Plugin Info Links
		$linksHook = \sprintf( 'plugin_action_links_%s', $this->settings->plugin );
		\add_filter( $linksHook, [ $this, 'pluginInfo' ], 11 );

		// Enqueue scripts for admin
		\add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts'] );

		// Ajax
		$getStatusHook = \sprintf( 'wp_ajax_%s_get_status', $this->settings->slug );
		\add_action( $getStatusHook, [ $this, 'ajaxStatus' ] );

		$unlinkHook = \sprintf( 'wp_ajax_%s_set_unlink', $this->settings->slug );
		\add_action( $unlinkHook, [ $this, 'ajaxUnlink'] );

		$linkHook = \sprintf( 'wp_ajax_%s_set_link', $this->settings->slug );
		\add_action( $linkHook, [ $this, 'ajaxLink'] );

		$getUpdateHook = \sprintf( 'wp_ajax_%s_get_update', $this->settings->slug );
		\add_action( $getUpdateHook, [ $this, 'ajaxUpdate' ] );

		// Print the popup
		\add_action( 'admin_footer', [ $this, 'printPopup' ], 1 );
	}

	/**
	 * Plugin Info
	 * @param  ARRAY $links
	 * @return ARRAY
	 */
	public function pluginInfo( $links ) {

		// License key
		$license = \get_option( $this->settings->slug . '_license', '' );

		if( ! isset( $license ) || empty( $license ) )
			return $links;

		// Remove the deactivate item and then add it to the end
		$auxLicense = $links['deactivate'];
		unset( $links['deactivate'] );
		
		$links['license'] = sprintf(
			'<a href="#open-license" class="letsgodev_license" data-slug="%s">%s</a>',
			$this->settings->slug,
			\esc_html__( 'Manager Licenses', 'letsgodev' )
		);

		// Add it to the end
		$links['deactivate'] = $auxLicense;

		return $links;
	}


	/**
	 * Scripts on the admin
	 * @return mixed
	 */
	public function enqueueScripts() {
		$screen = \get_current_screen();

		// In plugin page
		if ( ! isset( $screen->base ) || 'plugins' != $screen->base )
			return;

		\wp_register_script(
			'letsgodev-license-popup-js',
			$this->settings->url . 'resources/assets/scripts/license-popup.js',
			[ 'jquery' ], false, true
		);

		\wp_register_style(
			'letsgodev-license-popup-css',
			$this->settings->url . 'resources/assets/styles/license-popup.css'
		);

		\wp_enqueue_script( 'letsgodev-license-popup-js' );
		\wp_enqueue_style( 'letsgodev-license-popup-css' );

		$loadingIcon = \admin_url( 'images/spinner-2x.gif' );

		$data = [
			'loading_html'	=> \sprintf( '<img src="%s" alt="loading" />', $loadingIcon ),
			'ajax_url'		=> \admin_url( 'admin-ajax.php' ),
			'wpnonce'		=> \wp_create_nonce( 'letsgodev-wpnonce' ),
			'unlink_text'	=> \esc_html__( 'Unlink from this website', 'letsgodev' ),
			'link_text'		=> \esc_html__( 'Link this website', 'letsgodev' ),
			'update_text'	=> \esc_html__( 'Check updates', 'letsgodev' ),
		];

		\wp_localize_script( 'letsgodev-license-popup-js', 'letsgo', $data );
	}


	/**
	 * Verify the license status
	 * @return mixed
	 */
	public function ajaxStatus() {

		if ( ! wp_verify_nonce( $_POST[ 'wpnonce' ], 'letsgodev-wpnonce' ) ) {
			die ( 'Busted!');
		}
		
		// Check the license
		$isChecked = $this->api()->checkLicense();

		// Result from check the license
		$result = $this->api()->getLastResult( $this->settings->slug );

		if( ! $isChecked ) {
			\wp_send_json_error( [
				'isactive'	  => 0,
				'box_class'	  => 'error',
				'box_message' => $result['error'] ?? '',
			] );
		}

		// Check the status code
		switch( $result['data']['code'] ) {
			
			case 's205' :
			case 's215' :

				// License expire
				$licenseDates = \get_option( $this->settings->slug . '_license_dates', [] );
				$expire       = $result['data']['expire'] ?: ( $licenseDates['expire'] ?? '' );
				$expireLabel  = $this->api()->isExpired() ? \esc_html__( 'Expired', 'letsgodev' ) : \esc_html__( 'Expire', 'letsgodev' );

				$boxMessage = \sprintf(
					'%s <br /> %s: %s',
					\esc_html__( 'The license key is active to this domain', 'letsgodev' ),
					$expireLabel,
					$expire
				);

				$return = [
					'is_active'		=> 1,
					'is_unlink'		=> 0,
					'box_class'		=> 'success',
					'box_message'	=> $boxMessage,
				];

				break;

			case 's203' :

				$return = [
					'is_active'		=> 0,
					'is_unlink'		=> 1,
					'box_class'		=> 'warning',
					'box_message'	=> \esc_html__( 'The license key is unlinked. Please link it.', 'letsgodev' ),
				];
				break;

			default:
				\wp_send_json_error( [
					'is_active'		=> 0,
					'is_unlink'		=> 0,
					'box_class'		=> 'error',
					'box_message'	=> \esc_html__( 'Error in the License.', 'letsgodev' ),
				] );
		}
		
		\wp_send_json_success( $return );
	}

	/**
	 * Unassign the license from current domain
	 * @return mixed
	 */
	public function ajaxUnlink() {
		if ( ! \wp_verify_nonce( $_POST[ 'wpnonce' ], 'letsgodev-wpnonce' ) ) {
			die( 'Busted!');
		}


		// Check the license
		$isDeactivated = $this->api()->deactivate();

		// Get last result
		$result = $this->api()->getLastResult( $this->settings->slug );

		if( ! $isDeactivated ) {
			\wp_send_json_error( [
				'box_class'		=> 'error',
				'box_message'	=> $result['error'] ?? '',
			] );
		}
		
		\wp_send_json_success( [
			'box_class'		=> 'success',
			'box_message'	=> \esc_html__( 'The license key was successfully unlinked', 'letsgodev' ),
		] );
	}

	/**
	 * Unassign the license from current domain
	 * @return mixed
	 */
	public function ajaxLink() {
		if ( ! \wp_verify_nonce( $_POST[ 'wpnonce' ], 'letsgodev-wpnonce' ) ) {
			die( 'Busted!');
		}

		$licenseKey = \get_option( $this->settings->slug . '_license', '' );

		if( empty( $licenseKey ) ) {

			$result = [
				'error'		=> \esc_html__( 'The license is missing', 'letsgodev' ),
				'data'		=> [
					'method'	=> 'ajaxLink',
				]
			];
			
			// Logger
			Logger::message( $result, $this->settings->slug );

			\wp_send_json_error( [
				'box_class'		=> 'error',
				'box_message'	=> $result['error'] ?? '',
			] );
		}

		// Activating License
		$isActivated = $this->api()->activate( $licenseKey );

		// This transient is for print a notice on the top window
		if( ! $isActivated ) {

			// Get last result
			$result = $this->api()->getLastResult( $this->settings->slug );

			// Logger
			Logger::message( $result, $this->settings->slug );
		
			\wp_send_json_error( [
				'box_class'		=> 'error',
				'box_message'	=> $result['error'] ?? '',
			] );
		}


		\set_transient(
			$this->settings->slug . '_license_activated', true, HOUR_IN_SECONDS
		);
		
		\wp_send_json_success( [
			'box_class'		=> 'success',
			'box_message'	=> \esc_html__( 'The license key was successfully linked', 'letsgodev' ),
		] );
	}



	/**
	 * Ajax Check updates
	 * @return mixed
	 */
	public function ajaxUpdate() {
		if ( ! wp_verify_nonce( $_POST[ 'wpnonce' ], 'letsgodev-wpnonce' ) ) {
			die ( 'Busted!');
		}
		
		// Check the license
		$isChecked = $this->api()->checkUpdate( true );

		// Result from check the license
		$result = $this->api()->getLastResult( $this->settings->slug );

		if( ! $isChecked ) {
			$return = [
				'box_class'		=> 'error',
				'box_message'	=> $result['error'] ?? '',
			];

			\wp_send_json_error( $return );
		}

		$responseBody = \json_decode( $result[ 'data' ][ 'body' ] );
		
		// If is no update
		if( empty( $responseBody ) ) {
			$return = [
				'refresh'		=> false,
				'box_class'		=> 'info',
				'box_message'	=> esc_html__( 'No update available', 'letsgodev' ),
			];

			\wp_send_json_success( $return );
		}

		// Clean Cache Plugin
		\wp_clean_plugins_cache( true );

		$return = [
			'refresh'		=> true,
			'box_class'		=> 'success',
			'box_message'	=> esc_html__( 'Update available! Refreshing page...', 'letsgodev' ),
		];

		\wp_send_json_success( $return );
	}


	/**
	 * Print Box
	 * @return mixed
	 */
	public function printPopup() {
		$screen = \get_current_screen();

		if( ! \is_admin() || ! isset( $screen->base ) || 'plugins' != $screen->base )
			return;

		include $this->settings->dir . 'resources/views/popup.php';
	}
	
}