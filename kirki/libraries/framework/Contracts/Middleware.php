<?php

/**
 * Contract for middleware that can intercept and authorize API requests.
 * Middleware classes implementing this interface should perform authorization
 * or filtering logic before a route is executed.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Middleware
{
    /**
     * Handle an incoming request and return a boolean indicating access.
     *
     * @param Request $request The incoming request instance.
     * @param callable $next The next middleware in the chain.
     *
     * @return mixed The result of the next middleware or a redirect response.
     *
     * @since 1.0.0
     */
    public function handle(Request $request, callable $next);
}
