<?php
/**
 * Frontend API calls handler
 *
 * @package kirki
 */

namespace Kirki\API\Frontend;

use Kirki\API\Frontend\Controllers\FormController;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

use Kirki\API\Frontend\Controllers\CollectionController;


/**
 * Frontend API Class
 */
class FrontendApi
{

	/**
	 * Register all routes
	 *
	 * @return void
	 */
	public static function register()
	{
		// @todo: remove later
		// (new FormController())->register_routes();
		(new CollectionController())->register_routes();
	}
}
