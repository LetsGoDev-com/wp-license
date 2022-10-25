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

	protected $slug;
	protected $plugin;
	protected $name;

	protected $dir;
	protected $url;
	protected $base;
	
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

		// Plugin Info
		$this->name 	= $data['name'] ?? '';
		$this->plugin 	= $data['plugin'] ?? '';
		$this->slug 	= dirname( $this->plugin );
		$this->doc 		= $data['doc'] ?? '';

		// Plugin Path
		$this->dir 		= plugin_dir_path(__DIR__);
		$this->url 		= plugin_dir_url( __DIR__ );
		$this->base 	= dirname( plugin_basename( __DIR__ ) );

		// Api Info
		$this->api_url 	= $data['api_url'] ?? '';
		$this->product 	= $data['product'] ?? '';
		$this->domain 	= $data['domain'] ?? '';
		$this->version 	= $data['version'] ?? '';

		$this->email 	= $data['email'] ?? '';
		$this->redirect = $data['redirect'] ?? '';

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
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'letsgodev' ), '2.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'letsgodev' ), '2.1' );
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
			$moduleApp = new $moduleClass( $this );
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