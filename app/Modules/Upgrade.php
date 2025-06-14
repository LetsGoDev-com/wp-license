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
class Upgrade extends Module {

	/**
	 * Init Hook
	 * @return void
	 */
	public function iniHooks(): void {
		\add_action( 'after_setup_theme', [ $this, 'upgradePlugin' ] );
	}


	/**
	 * Upgrade plugin
	 * @return mixed
	 */
	public function upgradePlugin(): void {
		// Take over the update check
		\add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'checkUpdate'] );

		// Take over the Plugin info screen
		\add_filter( 'plugins_api', [ $this, 'pluginsAPICall' ], 10, 3);
	}


	/**
	 * Check if there is some update
	 * @param  OBJECT $checkedData
	 * @return OBJECT
	 */
	function checkUpdate( $checkedData ) {
		
		if ( ! \is_object( $checkedData ) || ! isset ( $checkedData->response ) ) {
			return $checkedData;
		}

		// Check update
		$isChecked = $this->api()->checkUpdate();

		// Get Results
		$result = $this->api()->getLastResult( $this->settings->slug );
		
		// If error
		if( ! $isChecked ) {
			return $checkedData;
		}

		$responseBlock = \json_decode( $result[ 'data' ][ 'body' ] );
 
		if( ! \is_array( $responseBlock ) || \count( $responseBlock ) < 1 ) {
			return $checkedData;
		}

		//retrieve the last message within the $responseBlock
		$responseBlock  = $responseBlock[ \count($responseBlock) - 1 ];
		$responsePlugin = $responseBlock->message ?? '';

		// Feed the update data into WP update
		if( \is_object( $responsePlugin ) && ! empty( (array)$responsePlugin ) ) {

			// When it is code_version in sl_action
			if( ! isset( $responsePlugin->new_version ) && isset( $responsePlugin->version ) ) {

				// If it is no a new version
				if( \version_compare( $responsePlugin->version, $this->settings->version, '<=' ) ) {
					return $checkedData;
				}

				$responsePlugin->new_version = $responsePlugin->version;
			}

			//include slug and plugin data
			$responsePlugin->slug   = $this->settings->slug;
			$responsePlugin->plugin = $this->settings->plugin;
			$responsePlugin->url    = $responsePlugin->homepage;

			//if sections are being set
			if ( isset( $responsePlugin->sections ) ) {
				$responsePlugin->sections = (array)$responsePlugin->sections;
			}

			//if banners are being set
			if ( isset( $responsePlugin->banners ) ) {
				$responsePlugin->banners = (array)$responsePlugin->banners;
			}

			//if icons being set, convert to array
			if ( isset( $responsePlugin->icons ) ) {
				$responsePlugin->icons = (array)$responsePlugin->icons;
			}

			$checkedData->response[ $this->settings->plugin ] = $responsePlugin;
		}

		return $checkedData;
	}


	/**
	 * Update process
	 * @param  [type] $def
	 * @param  string $action
	 * @param  [type] $args
	 * @return mixed
	 */
	public function pluginsAPICall( bool $def, string $action, \stdClass $args ) {
		
		if ( ! \is_object( $args ) || ! isset( $args->slug ) || $args->slug != $this->settings->slug ) {
			return $def;
		}

		// Plugin_information from the API
		$this->api()->setInfo();

		// Get Results
		$result = $this->api()->getLastResult( $this->settings->slug );

		// If error
		if( empty( $result ) ) {
			return new \WP_Error('plugins_api_failed', \esc_html__('An Unexpected HTTP Error occurred during the API request.' , 'letsgodev') . '&lt;/p> &lt;p>&lt;a href=&quot;?&quot; onclick=&quot;document.location.reload(); return false;&quot;>'. \esc_html__( 'Try again', 'letsgodev' ) .'&lt;/a>', $result[ 'error' ]);
		}

		$responseBlock = \json_decode( $result[ 'data' ][ 'body' ] );
		
		//retrieve the last message within the $responseBlock
		$responseBlock = $responseBlock[ \count($responseBlock) - 1 ];
		$responsePlugin = $responseBlock->message;

		// Feed the update data into WP updater
		if( \is_object( $responsePlugin ) && ! empty( (array) $responsePlugin ) ) {
			
			//include slug and plugin data
			$responsePlugin->slug     = $this->settings->slug;
			$responsePlugin->plugin   = $this->settings->plugin;

			// New version
			if( ! isset( $responsePlugin->new_version ) && isset( $responsePlugin->version ) ) {
				$responsePlugin->new_version = $responsePlugin->version;
			}

			//if sections are being set
			if( isset( $responsePlugin->sections ) ) {
				$responsePlugin->sections = (array)$responsePlugin->sections;
			}

			//if banners are being set
			if( isset( $responsePlugin->banners ) ) {
				$responsePlugin->banners = (array)$responsePlugin->banners;
			}

			//if icons being set, convert to array
			if( isset( $responsePlugin->icons ) ) {
				$responsePlugin->icons = (array)$responsePlugin->icons;
			}

			return $responsePlugin;
		}

		return $def;
	}
}