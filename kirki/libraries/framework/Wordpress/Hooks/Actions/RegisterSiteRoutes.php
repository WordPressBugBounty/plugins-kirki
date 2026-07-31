<?php

/**
 * WordPress init action that boots the SiteRouter for all site routes.
 * Registers rewrite rules, query vars, and dispatch hooks for front-end routes.
 *
 * @package    Framework
 * @subpackage Wordpress\Hooks\Actions
 * @since      1.0.0
 */
namespace Kirki\Framework\Wordpress\Hooks\Actions;

\defined('ABSPATH') || exit;
use Kirki\Framework\Route;
use Kirki\Framework\Routing\SiteRouter;
use Kirki\Framework\Wordpress\BaseHook;
use Kirki\Framework\Wordpress\Constants\HookNames;
use Kirki\Framework\Wordpress\Constants\HookTypes;
class RegisterSiteRoutes extends BaseHook
{
    /**
     * Get the name.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function get_name()
    {
        return HookNames::INIT;
    }
    /**
     * Get the type.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function get_type()
    {
        return HookTypes::ACTION;
    }
    /**
     * Handle.
     *
     * @param mixed $args The positional arguments.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function handle(...$args)
    {
        $site_routes = Route::get_site_routes();
        if (empty($site_routes)) {
            return;
        }
        $router = new SiteRouter(Route::get_site_namespace(), Route::get_routing_method());
        $router->boot($site_routes);
        Route::set_site_router($router);
    }
}
