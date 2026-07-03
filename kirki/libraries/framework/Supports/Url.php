<?php

/**
 * WordPress URL helper for building query-string URLs and performing redirects.
 * Uses add_query_arg and wp_safe_redirect with a JavaScript fallback when headers are already sent.
 * Supports redirect_back via wp_get_referer.
 *
 * @package    Framework
 * @subpackage Supports
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports;

\defined('ABSPATH') || exit;
class Url
{
    /**
     * Make.
     *
     * @param mixed $url The url.
     * @param mixed $data The data payload.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function make($url, $data = [])
    {
        $referer = $url;
        return add_query_arg($data, $referer);
    }
    /**
     * Redirect.
     *
     * @param mixed $url The url.
     * @param mixed $data The data payload.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function redirect($url, $data = [])
    {
        $referer = $url;
        $redirect_url = add_query_arg($data, $referer);
        static::perform_redirect($redirect_url);
    }
    /**
     * Redirect back.
     *
     * @param mixed $data The data payload.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function redirect_back($data = [])
    {
        $referer = wp_get_referer();
        $redirect_url = add_query_arg($data, $referer);
        static::perform_redirect($redirect_url);
    }
    /**
     * Perform redirect.
     *
     * @param mixed $redirect_url The redirect url.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected static function perform_redirect($redirect_url)
    {
        if (\headers_sent()) {
            $safe_url = esc_url($redirect_url);
            echo '<script>window.location.href = ' . wp_json_encode($safe_url) . ';</script>';
            exit;
        }
        wp_safe_redirect($redirect_url);
        exit;
    }
}
