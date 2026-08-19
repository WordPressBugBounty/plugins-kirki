<?php

/**
 * WordPress send_headers action that emits any cookies queued during the request lifecycle.
 * Covers ordinary page loads where no framework route sends the response.
 * Site routes and REST responses flush through their own dispatch points.
 *
 * @package    Framework
 * @subpackage Wordpress\Hooks\Actions
 * @since      1.0.0
 */
namespace Kirki\Framework\Wordpress\Hooks\Actions;

\defined('ABSPATH') || exit;
use Kirki\Framework\Managers\CookieManager;
use Kirki\Framework\Wordpress\BaseHook;
use Kirki\Framework\Wordpress\Constants\HookNames;
use Kirki\Framework\Wordpress\Constants\HookTypes;
use function Kirki\Framework\app;
class FlushQueuedCookies extends BaseHook
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
        return HookNames::SEND_HEADERS;
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
        app(CookieManager::class)->flush_queued_cookies();
    }
}
