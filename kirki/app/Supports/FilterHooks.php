<?php

namespace Kirki\App\Supports;

defined('ABSPATH') || exit;

use Exception;

class FilterHooks
{
    /**
     * @param string $app_slug
     * @param array $default_settings
     */
    public static function kirki_apps_configuration($app_slug, $default_settings)
    {
        $default_settings = is_array($default_settings) ? $default_settings : [];

        $filtered_settings = apply_filters('kirki_apps_configuration_' . $app_slug, $default_settings);

        if (!is_array($filtered_settings)) {
             throw new Exception(esc_html__(
                /* translators: %s: app slug */
                sprintf('The filter "kirki_apps_configuration_%s" must return an array.', $app_slug), 
                'kirki'
            ));
        }
        
        return $filtered_settings;
    }
}