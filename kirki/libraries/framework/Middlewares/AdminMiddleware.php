<?php

/**
 * Middleware to ensure the user is authenticated.
 * Blocks access to routes unless the user is logged in.
 *
 * @package    Framework
 * @subpackage Middlewares
 * @since      1.0.0
 */
namespace Kirki\Framework\Middlewares;

\defined('ABSPATH') || exit;
use Kirki\Framework\Contracts\Middleware;
use Kirki\Framework\Contracts\Request;
use Kirki\Framework\Exceptions\AuthorizationException;
use Kirki\Framework\Http\Response;
use Kirki\Framework\Wordpress\Constants\Capabilities;
use function Kirki\Framework\message;
class AdminMiddleware implements Middleware
{
    /**
     * Handle the incoming request and determine if the user is authenticated and has admin privileges.
     *
     * @param Request $request The incoming request instance.
     * @param callable $next The next middleware callback.
     *
     * @return mixed The result of the next middleware or a response.
     *
     * @throws AuthorizationException
     *
     * @since 1.0.0
     */
    public function handle(Request $request, callable $next)
    {
        if (is_user_logged_in() && current_user_can(Capabilities::MANAGE_OPTIONS)) {
            $request->from_admin_middleware = 'from_admin_middleware';
            return $next($request);
        }
        throw new AuthorizationException(message('auth.admin_required'), Response::FORBIDDEN);
    }
}
