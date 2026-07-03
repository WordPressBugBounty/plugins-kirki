<?php

/**
 * Exception thrown when an invalid or non-existent middleware is used.
 * This helps catch misconfigurations or typos in middleware assignment during route registration.
 *
 * @package    Framework
 * @subpackage Exceptions
 * @since      1.0.0
 */
namespace Kirki\Framework\Exceptions;

\defined('ABSPATH') || exit;
use Exception;
class InvalidMiddlewareException extends Exception
{
    // Custom logic can be added here if needed.
}
