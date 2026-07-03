<?php

/**
 * Exception thrown when an invalid or non-existent validation rule is used.
 *
 * @package    Framework
 * @subpackage Exceptions
 * @since      1.0.0
 */
namespace Kirki\Framework\Exceptions;

\defined('ABSPATH') || exit;
use Exception;
class InvalidValidationRuleException extends Exception
{
    // Custom logic can be added here if needed.
}
