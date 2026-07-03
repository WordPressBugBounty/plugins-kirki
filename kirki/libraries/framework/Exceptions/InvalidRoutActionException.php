<?php

/**
 * Exception thrown when a route action is invalid or improperly defined.
 * This includes issues like non-existent controllers, incorrect method names, or misconfigured route syntax.
 *
 * @package    Framework
 * @subpackage Exceptions
 * @since      1.0.0
 */
namespace Kirki\Framework\Exceptions;

\defined('ABSPATH') || exit;
use Exception;
class InvalidRoutActionException extends Exception
{
    // You can extend this later with custom message formatting or logging.
}
