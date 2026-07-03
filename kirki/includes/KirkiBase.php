<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Kirki\Ajax;
use Kirki\API;
use Kirki\Apps;
use Kirki\ContentManager;
use Kirki\Customizer;
use Kirki\ElementVisibilityConditions;
use Kirki\HelperFunctions;
use Kirki\Manager\PluginActiveEvents;
use Kirki\Manager\PluginDeactivateEvents;
use Kirki\Manager\PluginInitEvents;
use Kirki\Manager\PluginLoadedEvents;
use Kirki\Manager\PluginShortcode;

if ( ! class_exists( 'KirkiBase' ) ) {
	abstract class KirkiBase {
		/**
		 * Class constructor
		 */
		protected function __construct() {
			$current_limit = ini_get( 'memory_limit' );
			if ( HelperFunctions::convertToBytes( $current_limit ) < 512 * 1024 * 1024 ) {
				if ( function_exists( 'wp_raise_memory_limit' ) ) {
					wp_raise_memory_limit( '512M' );
				}
			}
			register_activation_hook( $this->get_plugin_file(), array( $this, 'activate' ) );
			register_deactivation_hook( $this->get_plugin_file(), array( $this, 'deactivate' ) );
			add_action( 'init', array( $this, 'plugin_init' ) );
			new PluginLoadedEvents();
			new PluginInitEvents();
			new PluginShortcode();


			Customizer::init();
		}

		protected static $instance = false;
		/**
		 * Initializes a singleton instance
		 *
		 * @return static
		 */
		public static function init() {

			if ( ! self::$instance ) {
				self::$instance = new static();
			}

			new Ajax();
			new API();
			new ContentManager();
			new ElementVisibilityConditions();

			return self::$instance;
		}

		public function plugin_init() {
			new Apps();
		}

		/**
		 * Do stuff upon plugin activation
		 *
		 * @return void
		 */
		public function activate() {
			new PluginActiveEvents();
		}

		/**
		 * Do stuff upon plugin deactivation
		 *
		 * @return void
		 */
		public function deactivate() {
			new PluginDeactivateEvents();
		}

	}
}
