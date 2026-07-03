<?php

/**
 * Exception thrown when a resource is not found.
 *
 * @package    Framework
 * @subpackage Exceptions
 * @since      1.0.0
 */
namespace Kirki\Framework\Exceptions;

\defined('ABSPATH') || exit;
use Exception;
class NotFoundException extends Exception
{
    // Custom logic can be added here if needed.
}
