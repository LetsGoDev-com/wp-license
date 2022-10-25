<?php
namespace LetsGoDev\Classes;

use LetsGoDev\Controllers\LicenseAPIController;

/**
 * Modules
 *
 * This abstract class is the parent of the modules
 * Save settings values and initialize methods
 *
 * @since      1.0.0
 * @package    LetsGoDev
 * @subpackage LetsGoDev/Classes
 * @author     LetsGoDev <support@letsgodev.com>
 */

abstract class Module {

	/**
	 * Settings
	 * @var null
	 */
	protected $settings = null;


	/**
	 * Class Construct
	 * @param array $settings
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
	}
	
	/**
	 * Init Settings
	 * @return mixed
	 */
	public function initSettings() {}

	/**
	 * Init Hooks
	 * @return mixed
	 */
	public function iniHooks() {}


	/**
	 * License Api
	 * @return object
	 */
	public function api() {
		if ( ! $this->api instanceof LicenseAPIController ) {
            $this->api = new LicenseAPIController( $this->settings );
        }

        return  $this->api;
	}


	/**
	 * All the modules begin executing run method
	 * @return mixed
	 */
	public function run() {
		$this->initSettings();
		$this->iniHooks();
	}
}