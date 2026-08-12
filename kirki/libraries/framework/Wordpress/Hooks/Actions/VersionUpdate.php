<?php

/**
 * WordPress plugin_update action that runs the version manager.
 *
 * @package    Framework
 * @subpackage Wordpress\Hooks\Actions
 * @since      1.0.0
 */
namespace Kirki\Framework\Wordpress\Hooks\Actions;

\defined('ABSPATH') || exit;
use Kirki\Framework\Wordpress\Constants\HookNames;
use Kirki\Framework\Wordpress\Constants\HookTypes;
use Kirki\Framework\Wordpress\BaseHook;
use Kirki\Framework\Managers\VersionUpdateManager;
class VersionUpdate extends BaseHook
{
    /**
     * Get the name of the hook.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_name()
    {
        return HookNames::ADMIN_INIT;
    }
    /**
     * Get the type of the hook.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_type()
    {
        return HookTypes::ACTION;
    }
    /**
     * Get the priority of the hook.
     *
     * @return int
     *
     * @since 1.0.0
     */
    public function get_priority()
    {
        return 5;
    }
    /**
     * Handle the hook.
     *
     * @param array $args The arguments passed to the hook.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function handle(...$args)
    {
        $version_manager = VersionUpdateManager::get_instance();
        $version_manager->run();
    }
}
