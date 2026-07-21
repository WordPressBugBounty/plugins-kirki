<?php

namespace Kirki\App\Supports\Facades;

defined( 'ABSPATH' ) || exit;

use Kirki\App\Managers\GlobalDataManager;
use Kirki\Framework\Facade;

/**
 * Global data Support Facade
 * @method static mixed get(string $key)
 * @method static void update(string $key, $value)
 * @method static array get_deprecated_global_style_blocks()
 * @method static void update_deprecated_global_style_blocks($styles)
 * @method static array get_style_blocks()
 * @method static void update_style_blocks($styles)
 * @method static array get_global_ui_controller()
 * @method static void update_global_ui_controller($controller)
 * @method static array get_global_ui_saved_data()
 * @method static void update_global_ui_saved_data($data)
 * @method static array get_global_custom_fonts()
 * @method static void update_global_custom_fonts($fonts)
 * @method static array get_variable_data()
 * @method static array normalize_variable_data(array $raw_data)
 * @method static array sort_variable_data(array $variable_data)
 * @method static array get_initial_viewports()
 * @method static array get_view_port_list()
 * 
 * @see \Kirki\App\Managers\GlobalDataManager
 */
class GlobalData extends Facade
{
    public static function get_accessor()
    {
        return GlobalDataManager::class;
    }
}
