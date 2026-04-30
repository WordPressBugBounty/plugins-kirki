<?php
/* THIS_FILE_IS_FREE */
/**
 * Kirki
 *
 * @package     kirki
 * Plugin Name: Kirki
 * Plugin URI: https://kirki.com
 * Description: Kirki is an all-in-one no-code builder that empowers users to build professional-grade WordPress sites without writing any code. It’s a promising glimpse into the future of website development.
 * Version: 6.0.2
 * Author: Kirki
 * Author URI: https://kirki.com
 * Text Domain: kirki
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 */

use Kirki\HelperFunctions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}



if ( ! class_exists( 'KirkiProMain' ) && ! class_exists( 'KirkiMain' ) && ! class_exists( 'Droip' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
	require_once __DIR__ . '/customizer/class-customizer.php';
	require_once __DIR__ . '/includes/KirkiBase.php';

	if (!defined('KIRKI_PLUGIN_FILE')) {
		define('KIRKI_PLUGIN_FILE', plugin_dir_path(__FILE__) . 'kirki.php');
	}

	final class KirkiMain extends KirkiBase {

		protected function get_plugin_file() {
			return __FILE__;
		}

		protected function load_version_specific_events() {
		}
	}

	/**
	 * Initilizes the main plugin
	 *
	 * @return \Kirki
	 */
	if ( ! function_exists( 'KirkiMain' ) ) {
		/**
		 * This function for entry point
		 */
		function KirkiMain() {
			return KirkiMain::init();
		}

		try {
			// kick-off the plugin.
			KirkiMain();
		} catch ( Exception $e ) {
			HelperFunctions::store_error_log( wp_json_encode( $e ) );
		}
	}
}