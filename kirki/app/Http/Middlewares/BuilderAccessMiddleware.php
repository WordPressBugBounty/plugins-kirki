<?php

namespace Kirki\App\Http\Middlewares;

defined('ABSPATH') || exit;

use Kirki\Framework\Contracts\Middleware;
use Kirki\Framework\Contracts\Request;
use Kirki\Framework\Exceptions\AuthorizationException;
use function Kirki\Framework\user;

class BuilderAccessMiddleware implements Middleware
{
    /**
     * Handle the incoming request and determine if the user has builder access.
     *
     * Builder access covers full + editor access levels — everything except
     * site-admin features (settings, license, import/export, apps management).
     *
     * @param Request $request The incoming request instance.
     * @param callable $next The next middleware or controller to execute.
     * @return mixed The result of the next middleware or a response.
     */
    public function handle(Request $request, callable $next)
    {
        if (!user()->has_builder_access()) {
            throw new AuthorizationException(__('You don\'t have the permission to access this request', 'kirki'));
        }

        return $next($request);
    }
}
