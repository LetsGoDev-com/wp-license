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
class Links extends Module {
 
	
	/**
	 * Init Hooks
	 * @return mixed
	 */
	public function iniHooks() {
		
		// Plugin Info Links
		\add_filter( 'plugin_row_meta', [ $this, 'pluginInfo' ], 10, 2 );
	}


	/**
	 * Plugin Info
	 * @param  array  $links
	 * @param  string $file
	 * @return array
	 */
	public function pluginInfo( array $links = [], string $file = '' ) {

		if( $file != $this->settings->plugin ) {
			return $links;
		}

		$newLinks = [
			'docs' => sprintf(
				'<a href="%s" target="_blank" title="%s">%s</a>',
				$this->settings->doc,
				$this->settings->name,
				\esc_html__( 'Documentation', 'letsgodev' )
			),
			'support' => sprintf(
				'<a href="%s" target="_blank" title="%s">%s</a>',
				\esc_url( 'https://www.letsgodev.com/contact/' ),
				$this->settings->name,
				\esc_html__( 'Support', 'letsgodev' )
			),
			'buy' => sprintf(
				'<a href="%s" target="_blank" title="%s">%s</a>',
				\esc_url( 'https://www.letsgodev.com/' ),
				$this->settings->name,
				\esc_html__( 'More Premiun Plugins', 'letsgodev' )
			),
		];

		return \array_merge( $links, $newLinks );
	}
}