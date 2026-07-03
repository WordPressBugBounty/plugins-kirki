<?php

/**
 * WordPress rest_api_init action that registers all Route instances with the REST API.
 * Iterates the static route registry and calls register on each.
 * Bridges the Route fluent API to WordPress REST endpoints.
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
use Kirki\Framework\Route;
class RegisterRestApi extends BaseHook
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
        return HookNames::REST_API_INIT;
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
        $routes = Route::get_routes();
        foreach ($routes as $route) {
            $route->register();
        }
    }
}
