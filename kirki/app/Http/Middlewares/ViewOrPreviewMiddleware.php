<?php

namespace Kirki\App\Http\Middlewares;

defined('ABSPATH') || exit;

use Kirki\App\Constants\AccessLevels;
use Kirki\App\Supports\EditorPreview;
use Kirki\Framework\Contracts\Middleware;
use Kirki\Framework\Contracts\Request;
use Kirki\Framework\Exceptions\AuthorizationException;
use function Kirki\Framework\user;

class ViewOrPreviewMiddleware implements Middleware
{
    /**
     * Handle the incoming request and determine if the user has view access.
     *
     * @param Request $request The incoming request instance.
     * @param callable $next The next middleware or controller to execute.
     * @return mixed The result of the next middleware or a response.
     */
    public function handle(Request $request, callable $next)
    {
        if (EditorPreview::has_valid_token()) {
            return $next($request);
        }

        $has_access = user()->has_access([
            AccessLevels::FULL_ACCESS,
            AccessLevels::CONTENT_ACCESS,
            AccessLevels::VIEW_ACCESS,
        ]);

        if (!$has_access) {
            throw new AuthorizationException(__('You don\'t have the permission to access this request', 'kirki'));
        }

        return $next($request);
    }
}