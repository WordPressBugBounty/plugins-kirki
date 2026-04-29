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
			$this->define_constants();
			register_activation_hook( $this->get_plugin_file(), array( $this, 'activate' ) );
			register_deactivation_hook( $this->get_plugin_file(), array( $this, 'deactivate' ) );
			add_action( 'init', array( $this, 'plugin_init' ) );
			new PluginLoadedEvents();
			new PluginInitEvents();
			$this->load_version_specific_events();
			new PluginShortcode();


			Customizer::init();
		}

		/**
		 * Initializes a singleton instance
		 *
		 * @return static
		 */
		public static function init() {
			static $instance = false;

			if ( ! $instance ) {
				$instance = new static();
			}

			new Ajax();

			new API();

			new ContentManager();

			new ElementVisibilityConditions();

			return $instance;
		}

		public function plugin_init() {
			new Apps();
		}

		/**
		 * Define plugin constants
		 *
		 * @return void
		 */
		public function define_constants() {
			require plugin_dir_path( $this->get_plugin_file() ) . 'config.php';
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

		/**
		 * Get the plugin file path
		 *
		 * @return string
		 */
		abstract protected function get_plugin_file();

		/**
		 * Load version-specific events (free vs pro)
		 *
		 * @return void
		 */
		abstract protected function load_version_specific_events();
	}
}
