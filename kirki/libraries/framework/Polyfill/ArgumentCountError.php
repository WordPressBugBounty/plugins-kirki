<?php

/**
 * Polyfill bootstrap for a namespaced ArgumentCountError compatible with PHP 7.1+.
 * Wraps the native class when available or defines a minimal Error subclass otherwise.
 * Loaded early so framework code can reference a consistent exception type across PHP versions.
 *
 * @package    Framework
 * @subpackage Polyfill
 * @since      1.0.0
 */
namespace Kirki\Framework\Polyfill;

\defined('ABSPATH') || exit;
if (!\class_exists('Kirki\\Framework\\Polyfill\\ArgumentCountError', \false)) {
    if (\class_exists('ArgumentCountError', \false)) {
        /**
         * Namespaced wrapper for the native ArgumentCountError class.
         *
         * @since 1.0.0
         */
        class ArgumentCountError extends \ArgumentCountError
        {
        }
    } else {
        /**
         * Polyfill for the ArgumentCountError class introduced in PHP 7.1.
         *
         * @since 1.0.0
         */
        class ArgumentCountError extends \Error
        {
        }
    }
}
