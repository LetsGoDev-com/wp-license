<?php

namespace LetsGoDev\Modules;

use LetsGoDev\Classes\Module;

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
		$linksHook = sprintf( 'plugin_action_links_%s', $this->settings->plugin );
		add_filter( $linksHook, [ $this, 'pluginInfo' ], 11 );

		// Enqueue scripts for admin
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts'] );

		// Ajax
		$getStatusHook = sprintf( 'wp_ajax_%s_get_status', $this->settings->slug );
		add_action( $getStatusHook, [ $this, 'ajaxStatus' ] );

		$unlinkHook = sprintf( 'wp_ajax_%s_set_unlink', $this->settings->slug );
		add_action( $unlinkHook, [ $this, 'ajaxUnlink'] );

		// Print the popup
		add_action( 'admin_footer', [ $this, 'printPopup' ], 1 );
	}

	/**
	 * Plugin Info
	 * @param  ARRAY $links
	 * @return ARRAY
	 */
	public function pluginInfo( $links ) {

		// License key
		$license = get_option( $this->settings->slug . '_license', '' );

		if( ! isset( $license ) || empty( $license ) )
			return $links;

		// Remove the deactivate item and then add it to the end
		$auxLicense = $links['deactivate'];
		unset( $links['deactivate'] );
		
		$links['license'] = sprintf(
			'<a href="#open-license" class="letsgodev_license" data-slug="%s">%s</a>',
			$this->settings->slug,
			esc_html__( 'Manager Licenses', 'letsgodev' )
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
		$screen = get_current_screen();

		// In plugin page
		if ( ! isset( $screen->base ) || 'plugins' != $screen->base )
			return;

		wp_register_script(
			'letsgodev-license-popup-js',
			$this->settings->url . 'resources/assets/scripts/license-popup.js',
			[ 'jquery' ], false, true
		);

		wp_register_style(
			'letsgodev-license-popup-css',
			$this->settings->url . 'resources/assets/styles/license-popup.css'
		);

		wp_enqueue_script( 'letsgodev-license-popup-js' );
		wp_enqueue_style( 'letsgodev-license-popup-css' );

		$loading_icon = admin_url( 'images/spinner-2x.gif' );

		$data = [
			'loading_html'	=> sprintf( '<img src="%s" alt="loading" />', $loading_icon ),
			'ajax_url'		=> admin_url( 'admin-ajax.php' ),
			'wpnonce'		=> wp_create_nonce( 'letsgodev-wpnonce' ),
			'unlink_text'	=> esc_html__( 'Unlink from this website', 'letsgodev' ),
		];

		wp_localize_script( 'letsgodev-license-popup-js', 'letsgo', $data );
	}


	/**
	 * Verify the license status
	 * @return mixed
	 */
	public function ajaxStatus() {

		if ( ! wp_verify_nonce( $_POST[ 'wpnonce' ], 'letsgodev-wpnonce' ) )
        	die ( 'Busted!');
		
		// Check the license
		$isChecked = $this->api()->checkLicense();

		// Result from check the license
		$result = $this->api()->getLastResult( $this->settings->slug );

		if( ! $isChecked ) {
			wp_send_json_error( [
				'isactive'	=> 0,
				'class'		=> 'error',
				'message'	=> $result['error'] ?? '',
			] );
		}


		$license_dates = get_option( $this->settings->slug . '_license_dates', [] );
		
		$expire = $result['data']['expire'] ?: ( $license_dates['expire'] ?? '' );

		// Check the status code
		switch( $result['data']['code'] ) {
			
			case 's205' :
			case 's215' :

				$box_message = sprintf(
					'%s <br /> %s',
					esc_html__( 'The license key is active to this domain', 'letsgodev' ),
					sprintf( esc_html__( 'Expire: %s', 'letsgodev'), $expire )
				);

				$success = true;
				$return = [
					'is_active'		=> 1,
					'box_class'		=> 'success',
					'box_message'	=> $box_message,
				];

				break;
			
			case 's203' :
				$success = false;
				$return = [
					'is_active'		=> 0,
					'box_class'		=> 'warning',
					'box_message'	=> esc_html__( 'The license key is unlinked', 'letsgodev' ),
				];
				break;
		}

		// If error
		if( ! $success ) {
			wp_send_json_error( $return );
		}
		
		
		wp_send_json_success( $return );
	}

	/**
	 * Unassign the license from current domain
	 * @return mixed
	 */
	public function ajaxUnlink() {
		if ( ! wp_verify_nonce( $_POST[ 'wpnonce' ], 'letsgodev-wpnonce' ) )
        	die( 'Busted!');


        // Check the license
		$isDeactivated = $this->api()->deactivate();

		// Get last result
		$result = $this->api()->getLastResult( $this->settings->slug );

		if( ! $isDeactivated ) {
			wp_send_json_error( [
				'box_class'		=> 'error',
				'box_message'	=> $result['error'] ?? '',
			] );
		}
		
		wp_send_json_success( [
			'box_class'		=> 'success',
			'box_message'	=> esc_html__( 'The license key was successfully unlinked', 'letsgodev' ),
		] );
	}


	/**
	 * Print Box
	 * @return mixed
	 */
	public function printPopup() {
		$screen = get_current_screen();

		if( ! is_admin() || ! isset( $screen->base ) || 'plugins' != $screen->base )
			return;

		include $this->settings->dir . 'resources/views/popup.php';
	}


	
}