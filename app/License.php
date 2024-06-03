<?php

namespace LetsGoDev\Core;

/**
 * License Class
 *
 * This class defines all code necessary to run
 *
 * @since      1.0.0
 * @package    LetsGoDev
 * @subpackage LetsGoDev/Classes
 * @author     LetsGoDev <support@letsgodev.com>
 */
class License {


	/**
	 * Settings
	 * @var \sdtClass
	 */
	protected $settings;
	
	public $api;

	protected $modules = [
		'i18n',
		'Notice',
		'Links',
		'Popup',
		'Upgrade',
	];

	/**
	 * License Construct
	 * @param array $data
	 */
	function __construct( $data = [] ) {

		$this->settings = new \stdClass();

		// Plugin Info
		$this->settings->name 	= $data['name'] ?? '';
		$this->settings->plugin = $data['plugin'] ?? '';
		$this->settings->slug 	= \dirname( $this->settings->plugin );
		$this->settings->doc 	= $data['doc'] ?? '';

		// Plugin Path
		$this->settings->dir 	= \trailingslashit( \plugin_dir_path(__DIR__) );
		$this->settings->url 	= \trailingslashit( \plugin_dir_url( __DIR__ ) );
		$this->settings->base 	= \trailingslashit( \dirname( plugin_basename( __DIR__ ) ) );

		// Api Info
		$this->settings->api_url 	= $data['api_url'] ?? '';
		$this->settings->product 	= $data['product'] ?? '';
		$this->settings->domain 	= $data['domain'] ?? '';
		$this->settings->version 	= $data['version'] ?? '';

		$this->settings->email 		= $data['email'] ?? '';
		$this->settings->redirect 	= $data['redirect'] ?? '';

		// Register Modules
		$this->registerModules();
	}


	/**
	 * Magic Getter
	 * @param  string $name
	 * @return mixed
	 */
	public function __get( string $name ) {
		return $this->{$name} ?? null;
	}


	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		\_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'letsgodev' ), '2.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		\_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'letsgodev' ), '2.1' );
	}


	/**
	 * Register Modules
	 * @return mixed
	 */
	public function registerModules() {

		$modulesNamespace = '\\LetsGoDev\\Modules\\';

		foreach( $this->modules as $module ) {
			$moduleClass = $modulesNamespace . $module;

			if( ! \class_exists( $moduleClass ) ) {
				continue;
			}

			// Instance Module
			$moduleApp = new $moduleClass( $this->settings );
			$moduleApp->run();

			// Set API
			if( empty( $this->api ) ) {
				$this->api = $moduleApp->api();
			}
		}
	}


	/**
	 * Has License
	 * @return boolean
	 */
	public function hasLicense() {
		return $this->api->hasLicense();
	}


	/**
	 * is Active
	 * @return boolean
	 */
	public function isActive() {
		return $this->api->isActive();
	}

}