<?php

/**
 * Redirect response for site route controllers.
 *
 * @package    Framework
 * @subpackage Http
 * @since      1.0.0
 */
namespace Kirki\Framework\Http;

\defined('ABSPATH') || exit;
class RedirectResponse
{
    /**
     * The redirect target URL.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $url;
    /**
     * The HTTP redirect status code.
     *
     * @var int
     *
     * @since 1.0.0
     */
    protected $status;
    /**
     * Create a new RedirectResponse instance.
     *
     * @param string $url The redirect target URL.
     * @param int $status The HTTP status code.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct(string $url, int $status = 302)
    {
        $this->url = $url;
        $this->status = $status;
    }
    /**
     * Get the redirect URL.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_url()
    {
        return $this->url;
    }
    /**
     * Get the HTTP status code.
     *
     * @return int
     *
     * @since 1.0.0
     */
    public function get_status()
    {
        return $this->status;
    }
    /**
     * Send the redirect and terminate the request.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function send()
    {
        wp_safe_redirect($this->url, $this->status);
        exit;
    }
}
