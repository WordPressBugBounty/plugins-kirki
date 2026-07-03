<?php

namespace Kirki\App;

use Kirki\Framework\Sanitizer;

defined('ABSPATH') || exit;

if (!function_exists('Kirki\App\get_timezone')) {
    function get_timezone($is_utc = false) {
        if ($is_utc) {
            return 'UTC';
        }

        return wp_timezone();
    }
}

if (!function_exists('Kirki\App\get_upload_directory')) {
    /**
     * Get the base upload directory path
     * 
     * @return string
     */
    function get_upload_directory() {
        return wp_upload_dir()['basedir'];
    }
}

if (!function_exists('Kirki\App\get_upload_directory_url')) {
    /**
     * Get the base upload directory url
     * 
     * @return string
     */
    function get_upload_directory_url() {
        return wp_upload_dir()['baseurl'];
    }
}

if (!function_exists('Kirki\App\to_boolean')) {
    /**
     * Return boolean value
     * @param mixed $value
     * @return bool
     */
    function to_boolean($value) {
        if (is_bool($value)) {
            return $value;
        }

        if (is_null($value)) {
            return false;
        }

        if (is_string($value)) {
            if ($value === '' || $value === '0' || strtolower($value) === 'false') {
                return false;
            }
        }

        if (is_numeric($value) && (int) $value === 0) {
            return false;
        }

        if (is_array($value) || is_object($value)) {
            return !empty($value);
        }

        return true;
    }
}

if (!function_exists('Kirki\App\is_truthy')) {
    /**
     * Return is empty array
     * @param mixed $value
     * @return bool
     */
    function is_truthy($value) {
        $value = to_boolean($value);

        return $value === true;
    }
}

if (!function_exists('Kirki\App\is_falsy')) {
    /**
     * Return is empty array
     * @param mixed $value
     * @return bool
     */
    function is_falsy($value) {
        $value = to_boolean($value);
        
        return $value === false;
    }
}

if (!function_exists('Kirki\App\is_not_empty_array')) {
    /**
     * Return is not empty array
     * @param mixed $value
     * @return bool
     */
    function is_not_empty_array($value) {
        if (!is_array($value)) {
            return false;
        }

        return count($value) > 0;
    }
}

if (!function_exists('Kirki\App\get_editor_mode')) {
    /**
     * Get editor mode value
     * 
     * @return string
     */
    function get_editor_mode() {
        return 'kirki';
    }
}

if (!function_exists('Kirki\App\soft_flush_rewrite_rules')) {
    /**
     * Soft flush rewrite rules
     * 
     * @return void
     */
    function soft_flush_rewrite_rules() {
        flush_rewrite_rules(false);
    }
}


if (!function_exists('Kirki\App\get_request_header')) {
    /**
     * Get request header by header name with sanitization
     * 
     * @param string $header_name
     * 
     * @return string|null
     */
    function get_request_header(string $header_name) {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            return Sanitizer::apply_rule($headers[$header_name] ?? null, Sanitizer::TEXT);
        }
        
        // Fallback because `getallheaders()` is not guaranteed to exist in every PHP environment
        $normalized_name = strtolower(trim($header_name));
        $content_headers = ['content-type', 'content-length', 'content-md5'];

        $header_key = in_array($normalized_name, $content_headers, true) 
            ? str_replace('-', '_', strtoupper($normalized_name)) 
            : 'HTTP_' . str_replace('-', '_', strtoupper($normalized_name));

        $value = filter_input(INPUT_SERVER, $header_key, FILTER_UNSAFE_RAW);
        
        if (is_null($value) || $value === '' || $value === false) {
            return null;
        }

        return Sanitizer::apply_rule($value, Sanitizer::TEXT);
    }
}