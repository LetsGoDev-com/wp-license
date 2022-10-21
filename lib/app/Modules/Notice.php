<?php

namespace LetsGoDev\Modules;

use LetsGoDev\Classes\Module;
use LetsGoDev\Classes\Logger;

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
	function iniHooks() {
		// Activating license
		add_action( 'admin_init', [ $this, 'activateLicense' ] );

		// Box to enter the license
		add_action( 'admin_notices', [ $this, 'printLicenseBox' ] );

		// Alert message when the license expired
		add_action( 'admin_notices', [ $this, 'printLicenseExpired' ] );
	}

	

	/**
	 * Box on the top to enter the license
	 * @return mixed
	 */
	public function printLicenseBox() {

		if( $this->api()->hasLicense() ) {
			return;
		}

		$name 	= $this->setting->name;
		$slug 	= $this->setting->slug;
		$error 	= $this->notice ?: '';

		include LETSGO_LICENSE_PATH . 'resources/views/license-box.php';
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

		$name 	= $this->setting->name;
		$expire = $licensedates['expire'] ?? '';

		include LETSGO_LICENSE_PATH . 'resources/views/license-expired.php';
	}


	/**
	 * Activate License
	 * @param  string $license_key
	 * @return mixed
	 */
	private function activateLicense() {

		// Check if the license key was sent by POST
		if( ! isset( $_POST[ $this->settings->slug . '_license_key' ] ) ) {
			return;
		}

		// Remove html to license key
		$licenseKey = esc_html( $_POST[ $this->settings->slug . '_license_key' ] );
		
		// Activating License
		$result = $this->api()->activate( $licenseKey );

		if( ! $result['success'] ) {
			$this->notice = $result['data']['error'];
		}
		
		return true;
	}
}