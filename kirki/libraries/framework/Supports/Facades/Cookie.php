<?php

/**
 * Facade proxy for the CookieManager factory and queue.
 * Exposes make, forever, forget, queue, and the queue inspection methods.
 * Entry point for setting cookies from anywhere in the application.
 *
 * @package    Framework
 * @subpackage Supports\Facades
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports\Facades;

\defined('ABSPATH') || exit;
use Kirki\Framework\Facade;
// phpcs:disable Generic.Files.LineLength.TooLong
/**
 * Facade proxy for the CookieManager factory and queue.
 *
 * @method static \Framework\Http\Cookie make(string $name, string $value = '', int $minutes = 0, ?string $path = null, ?string $domain = null, ?bool $secure = null, bool $http_only = true, bool $raw = false, ?string $same_site = null)
 * @method static \Framework\Http\Cookie forever(string $name, string $value = '', ?string $path = null, ?string $domain = null, ?bool $secure = null, bool $http_only = true, bool $raw = false, ?string $same_site = null)
 * @method static \Framework\Http\Cookie forget(string $name, ?string $path = null, ?string $domain = null)
 * @method static void expire(string $name, ?string $path = null, ?string $domain = null)
 * @method static void queue(...$parameters)
 * @method static void unqueue(string $name, ?string $path = null)
 * @method static bool has_queued(string $name, ?string $path = null)
 * @method static \Framework\Http\Cookie|mixed queued(string $name, $default = null, ?string $path = null)
 * @method static array get_queued_cookies()
 * @method static void flush_queued_cookies()
 * @method static \Framework\Managers\CookieManager set_default_path_and_domain(?string $path, ?string $domain, bool $secure = false, ?string $same_site = null)
 * @method static array get_defaults()
 * @see    \Framework\Managers\CookieManager
 */
class Cookie extends Facade
{
    /**
     * Get the accessor.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public static function get_accessor()
    {
        return 'cookie';
    }
}
