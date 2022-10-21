<?php

namespace LetsGoDev\Modules;

use LetsGoDev\Classes\Module;

/**
 * International languages
 *
 * This class defines the folder where the language files are located
 *
 * @since      1.0.0
 * @package    LetsGoDev
 * @subpackage LetsGoDev/Modules
 * @author     LetsGoDev <support@letsgodev.com>
 */
class i18n extends Module {
	
	/**
	 * Init Hooks
	 * @return mixed
	 */
	function iniHooks() {
		\add_action( 'plugins_loaded', [ $this, 'loadTextdomain' ] );
	}


	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Letsgodev_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 */
	public function loadTextdomain() {

		\load_plugin_textdomain(
			'letsgodev',
			false,
			$this->settings->base . '/lib/resources/languages/'
		);
	}
}