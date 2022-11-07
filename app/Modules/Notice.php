<?php

namespace LetsGoDev\Modules;

use LetsGoDev\Classes\Module;

/**
 * Box where the user enter the license
 *
 *
 * @since      1.0.0
 * @package    LetsGoDev
 * @subpackage LetsGoDev/Modules
 * @author     LetsGoDev <support@letsgodev.com>
 */
class Notice extends Module {

	protected $notice;

	/**
	 * Init Hooks
	 * @return mixed
	 */
	public function iniHooks() {
		// Activating license
		add_action( 'admin_init', [ $this, 'activateLicense' ] );

		// Box to enter the license
		add_action( 'admin_notices', [ $this, 'printLicenseBox' ] );

		// Box when the license is activated
		add_action( 'admin_notices', [ $this, 'printLicenseActivated' ] );

		// Alert message when the license expired
		add_action( 'admin_notices', [ $this, 'printLicenseExpired' ] );

		// Enqueue scripts for admin
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts'] );
	}


	/**
	 * Scripts on the admin
	 * @return mixed
	 */
	public function enqueueScripts() {
		$screen = get_current_screen();

		wp_register_style(
			'letsgodev-license-notice-css',
			$this->settings->url . 'resources/assets/styles/license-notice.css'
		);

		wp_register_script(
			'letsgodev-license-notice-js',
			$this->settings->url . 'resources/assets/scripts/license-notice.js',
			[ 'jquery' ], false, true
		);

		wp_enqueue_script( 'letsgodev-license-notice-js' );
		wp_enqueue_style( 'letsgodev-license-notice-css' );


		$loading_icon = admin_url( 'images/spinner-2x.gif' );

		$data = [
			'wpnonce'		=> wp_create_nonce( 'letsgodev-wpnonce' ),
			'hasRedirect'	=> ! empty( $this->settings->redirect ),
			'redirect'		=> $this->settings->redirect,
		];

		wp_localize_script( 'letsgodev-license-popup-js', 'letsgoNotice', $data );
	}


	/**
	 * Box on the top to enter the license
	 * @return mixed
	 */
	public function printLicenseBox() {

		if( $this->api()->hasLicense() ) {
			return;
		}

		$result = $this->api()->getLastResult( $this->settings->slug );

		$name 	= $this->settings->name;
		$slug 	= $this->settings->slug;


		if( ! empty( $result['error'] ) ) {
			$error = print_r( [
				'error'	=> $result['error'],
				'code'	=> $result['data']['code'] ?? '',
			], true);
		}

		include $this->settings->dir . 'resources/views/license-box.php';
	}


	/**
	 * Message when the license expired
	 * @return html
	 */
	public function printLicenseExpired() {

		if( ! $this->api()->isExpired() ) {
			return;
		}

		// Get License Info
		$licensedates = get_option( $this->settings->slug . '_license_dates', [] );

		$name 	= $this->settings->name;
		$expire = $licensedates['expire'] ?? '';

		include $this->settings->dir . 'resources/views/license-expired.php';
	}


	/**
	 * Print notice when the license is activated
	 * @return html
	 */
	public function printLicenseActivated() {

		$isActivated = \get_transient( $this->settings->slug . '_license_activated' );

		if( ! $isActivated ) {
			return;
		}

		// Remove transient
		\delete_transient( $this->settings->slug . '_license_activated' );

		$loadingHtml 	= sprintf(
			'<img src="%s" alt="loading" />', admin_url( 'images/spinner-2x.gif' )
		);

		$name 			= $this->settings->name;
		$hasRedirect 	= ! empty( $this->settings->redirect );

		include $this->settings->dir . 'resources/views/license-activated.php';
	}


	/**
	 * Activate License
	 * @param  string $license_key
	 * @return mixed
	 */
	public function activateLicense() {

		// Check if the license key was sent by POST
		if( ! isset( $_POST[ $this->settings->slug . '_license_key' ] ) ) {
			return;
		}

		// Remove html to license key
		$licenseKey = esc_html( $_POST[ $this->settings->slug . '_license_key' ] );
		
		// Activating License
		$isActivated = $this->api()->activate( $licenseKey );

		// If it is activated
		if( $isActivated ) {
			\set_transient(
        		$this->settings->slug . '_license_activated', true, HOUR_IN_SECONDS
        	);
		}

		// If there is a redirect
		//if( $isActivated && ! empty( $this->settings->redirect ) ) {
		//	wp_redirect( $this->settings->redirect );
        //	exit;
		//}
		
		return $isActivated;
	}
}