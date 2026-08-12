<?php

/**
 * Loads action and filter hook classes from config/hooks.php and tags them for container discovery.
 * Ensures RegisterRestApi is always registered even when config is empty.
 * Boots hook instances onto WordPress during application boot.
 *
 * @package    Framework
 * @subpackage Wordpress
 * @since      1.0.0
 */
namespace Kirki\Framework\Wordpress;

\defined('ABSPATH') || exit;
use Kirki\Framework\ServiceProvider;
use Kirki\Framework\Wordpress\Hooks\Actions\VersionUpdate;
use Kirki\Framework\Wordpress\Hooks\Actions\RegisterRestApi;
use Kirki\Framework\Wordpress\Hooks\Actions\RegisterSiteRoutes;
class HookServiceProvider extends ServiceProvider
{
    /**
     * The defaults.
     *
     * @var array<string, array<string>>
     *
     * @since 1.0.0
     */
    protected static $defaults = ['actions' => [RegisterRestApi::class, RegisterSiteRoutes::class, VersionUpdate::class], 'filters' => []];
    /**
     * Register the hooks to the application.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function register()
    {
        $hooks = $this->hooks();
        $this->app->tag($hooks['actions'], 'hook.actions');
        $this->app->tag($hooks['filters'], 'hook.filters');
    }
    /**
     * Get the hooks from the configuration file.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function hooks()
    {
        $hooks_path = $this->app->config_path('hooks.php');
        if (!\file_exists($hooks_path)) {
            return static::$defaults;
        }
        $hooks = (include $hooks_path);
        if (empty($hooks)) {
            return static::$defaults;
        }
        $default_action_hooks = [];
        foreach (static::$defaults['actions'] as $action) {
            if (!\in_array($action, $hooks['actions'], \true)) {
                $default_action_hooks[] = $action;
            }
        }
        if (!empty($default_action_hooks)) {
            $hooks['actions'] = \array_merge($default_action_hooks, $hooks['actions']);
        }
        return ['actions' => $hooks['actions'] ?? [], 'filters' => $hooks['filters'] ?? []];
    }
    /**
     * Add the action hooks on after the application booted.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function boot()
    {
        $actions = $this->app->tagged('hook.actions');
        foreach ($actions as $action) {
            add_action($action->get_name(), [$action, 'handle'], $action->get_priority(), $action->get_args_count());
        }
        $filters = $this->app->tagged('hook.filters');
        foreach ($filters as $filter) {
            add_filter($filter->get_name(), [$filter, 'handle'], $filter->get_priority(), $filter->get_args_count());
        }
    }
}
