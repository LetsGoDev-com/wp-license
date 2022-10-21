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
	protected $base;
	protected $name;
	
	protected $error_title;
	protected $error_desc;
	protected $is_active = false;
	protected $transient_hours = 24;


	protected $modules = [
		'i18n',
		'Notice',
		'Popup',
		'Upgrade',
	];

	/**
	 * License Construct
	 * @param array $data
	 */
	function __construct( $data = [] ) {

		if( ! isset( $data['name'] ) || ! isset( $data['base'] ) )
			throw new Exception( esc_html__( 'Wrong information', 'letsgodev' ) );

		$this->name 	= $data['name'] ?? '';
		$this->plugin 	= $data['plugin'] ?? '';
		$this->slug 	= dirname( $this->plugin );

		$this->base 	= $data['library_base'] ?? '';
		//$this->dir 		= $data['library_dir'] ?? '';
		$this->doc 		= $data['doc'] ?? '';

		$this->api_url 	= $data['api_url'] ?? '';
		$this->product 	= $data['product'] ?? '';
		$this->domain 	= $data['domain'] ?? '';
		$this->version 	= $data['version'] ?? '';

		$this->email 	= $data['email'] ?? '';
		$this->redirect = $data['redirect'] ?? '';


		$this->registerModules();
	}


	/**
	 * Define Constant
	 * @return mixed
	 */
	public function defineConstant() {

		if( ! defined( 'LETSGO_LICENSE_PATH' ) ) {
			define( 'LETSGO_LICENSE_PATH', dirname( __FILE__ ) );
		}

		define('LETSGO_LICENSE_DIR' , plugin_dir_path(__FILE__));
		define('LETSGO_LICENSE_URL' , plugin_dir_url(__FILE__));
		define('LETSGO_LICENSE_BASE' , plugin_basename( __FILE__ ));
		
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

			$moduleApp = new $moduleClass( $this );
			$moduleApp->run();
		}
	}
}