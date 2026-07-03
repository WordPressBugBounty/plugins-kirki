<?php

/**
 * Contract for WordPress shortcode handlers.
 * Requires a name accessor and a callback matching the add_shortcode signature.
 * Standardizes shortcode registration across the plugin.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Shortcode
{
    /**
     * Get the shortcode name.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_name();
    /**
     * The shortcode callback function.
     *
     * @param mixed $attributes The attributes array.
     * @param string $content The content.
     * @param string $shortcode_tag The shortcode tag.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function callback($attributes, string $content = '', string $shortcode_tag = '');
}
