<?php

namespace Kirki\App\Supports;

defined('ABSPATH') || exit;

class Canvas
{
    /**
     * @return string
     */
    public static function get_full_canvas_template_path()
	{
		return static::normalize_full_canvas_template_path(KIRKI_FULL_CANVAS_TEMPLATE_PATH);
	}

    /**
     * @param string $path
     * @return string
     */
    public static function normalize_full_canvas_template_path(string $path)
	{
		// replace if has kirki-pro => kirki
		if (strpos($path, 'kirki-pro') !== false) {
			$path = str_replace('kirki-pro', 'kirki', $path);
		}

		return $path;
	}
}