<?php

namespace Kirki\App\Http\Middlewares;

defined('ABSPATH') || exit;

use Kirki\Framework\Contracts\Middleware;
use Kirki\Framework\Contracts\Request;
use Kirki\Framework\Exceptions\AuthorizationException;
use Kirki\Framework\Http\Response;

/**
 * Verifies the WordPress REST nonce (action "wp_rest") for otherwise public
 * endpoints such as the front-end form submission route.
 *
 * The front-end form client sends the nonce in the `X-WP-Nonce` header
 * (see builder/src/lib/api.js, populated from `wp_kirki.nonce`, which is
 * `wp_create_nonce( 'wp_rest' )`). Requiring a valid nonce ensures a submission
 * originates from a genuinely rendered page instead of a blind unauthenticated
 * POST, restoring the check that existed in the deprecated FormController.
 */
class VerifyRestNonceMiddleware implements Middleware
{
    /**
     * Handle the incoming request and reject it when the nonce is missing or invalid.
     *
     * @param Request  $request The incoming request instance.
     * @param callable $next    The next middleware or controller to execute.
     * @return mixed
     */
    public function handle(Request $request, callable $next)
    {
        $nonce = '';

        if (!empty($_SERVER['HTTP_X_WP_NONCE'])) {
            $nonce = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE']));
        }

        if ($nonce === '') {
            $nonce = (string) $request->get_string('_wpnonce', '');
        }

        if ($nonce === '' || !wp_verify_nonce($nonce, 'wp_rest')) {
            throw new AuthorizationException(
                __('Nonce verification failed', 'kirki'),
                Response::FORBIDDEN
            );
        }

        return $next($request);
    }
}
