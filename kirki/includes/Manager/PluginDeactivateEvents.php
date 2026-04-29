<?php
/**
 * Plugin deactive events handler
 *
 * @package kirki
 */

namespace Kirki\Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\HelperFunctions;


/**
 * Do some task during plugin deactivation
 */
class PluginDeactivateEvents {


	/**
	 * Initilize the class
	 *
	 * @return void
	 */
	public function __construct() {
		
		// Flush rewrite rules on deactivation
		flush_rewrite_rules( true );
	}

	

}
