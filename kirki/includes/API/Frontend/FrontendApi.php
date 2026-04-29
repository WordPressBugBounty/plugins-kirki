<?php
/**
 * Frontend API calls handler
 *
 * @package kirki
 */

namespace Kirki\API\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\API\Frontend\Controllers\CollectionController;
use Kirki\API\Frontend\Controllers\FormController;


/**
 * Frontend API Class
 */
class FrontendApi {

	/**
	 * Register all routes
	 *
	 * @return void
	 */
	public static function register() {
		( new FormController() )->register_routes();
		( new CollectionController() )->register_routes();
	}
}
