<?php

/**
 * Trait for HTTP response classes to attach cookies fluently.
 * Attached cookies are pushed into the CookieManager queue rather than stored on the response.
 * This keeps a single emission path for responses the framework sends and those WordPress serializes.
 *
 * @package    Framework
 * @subpackage Http\Concerns
 * @since      1.0.0
 */
namespace Kirki\Framework\Http\Concerns;

\defined('ABSPATH') || exit;
use Kirki\Framework\Http\Cookie;
use Kirki\Framework\Managers\CookieManager;
use function Kirki\Framework\app;
trait InteractsWithCookies
{
    /**
     * Attach a cookie to the response.
     *
     * Accepts either a Cookie instance or the arguments accepted by the cookie factory.
     * The cookie is queued immediately and emitted at the next flush point.
     *
     * @param mixed $parameters The cookie instance or the factory arguments.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function with_cookie(...$parameters)
    {
        $this->cookie_manager()->queue(...$parameters);
        return $this;
    }
    /**
     * Attach several cookies to the response.
     *
     * Accepts a list of Cookie instances or an associative array of names and values.
     *
     * @param array $cookies The cookies to attach.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function with_cookies(array $cookies)
    {
        foreach ($cookies as $name => $cookie) {
            if ($cookie instanceof Cookie) {
                $this->with_cookie($cookie);
                continue;
            }
            $this->with_cookie((string) $name, (string) $cookie);
        }
        return $this;
    }
    /**
     * Remove a previously attached cookie before it is emitted.
     *
     * @param string $name The name of the cookie.
     * @param string|null $path The path of the cookie, or null to remove every path.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function without_cookie(string $name, ?string $path = null)
    {
        $this->cookie_manager()->unqueue($name, $path);
        return $this;
    }
    /**
     * Get the cookie manager instance.
     *
     * @return CookieManager
     *
     * @since 1.0.0
     */
    protected function cookie_manager()
    {
        return app(CookieManager::class);
    }
}
