<?php
/**
 * Admin panel kirki entry point
 *
 * @package kirki
 */

namespace Kirki;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Kirki\Admin\AdminMenu;
use Kirki\Admin\PostActions;
use Kirki\Admin\EditWithButton;

/**
 * Kirki Admin
 */
class Admin {
	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function __construct() {

		new AdminMenu();
		new PostActions();
		new EditWithButton();
	}

	/**
	 * Plugin activation link
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions action list.
	 * @return array
	 */
	// public function plugin_action_links( $actions ) {
	// if ( ! HelperFunctions::is_pro_user() ) {
	// $actions['kirki_pro_link'] =
	// '<a href="https://kirki.com/pricing/?utm_source=kirki_dashboard&utm_medium=wp_dashboard&utm_campaign=upgrade_pro&referrer=wordpress_dashboard" target="_blank">
	// <span style="color: #7338d6; font-weight: bold;">Upgrade Pro</span>
	// </a>';
	// }

	// return $actions;
	// }
}
