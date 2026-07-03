<?php

/**
 * Distinguishes main admin menu entries from submenu entries.
 * Consumed by the Menu base class to route render calls to add_menu_page or add_submenu_page.
 * Keeps admin menu registration type-safe.
 *
 * @package    Framework
 * @subpackage Wordpress\Constants
 * @since      1.0.0
 */
namespace Kirki\Framework\Wordpress\Constants;

\defined('ABSPATH') || exit;
class MenuTypes
{
    /**
     * Wordpress admin menu type main menu
     * 
     * @since 1.0.0
     */
    public const MAIN_MENU = 'main-menu';
    /**
     * Wordpress admin menu type sub menu
     * 
     * @since 1.0.0
     */
    public const SUB_MENU = 'sub-menu';
}
