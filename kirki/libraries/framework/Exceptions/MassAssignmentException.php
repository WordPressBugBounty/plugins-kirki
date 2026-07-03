<?php

/**
 * Exception thrown when a mass assignment is attempted on a model that is not allowed.
 *
 * @package    Framework
 * @subpackage Exceptions
 * @since      1.0.0
 */
namespace Kirki\Framework\Exceptions;

use RuntimeException;
\defined('ABSPATH') || exit;
class MassAssignmentException extends RuntimeException
{
    //
}
