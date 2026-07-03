<?php

/**
 * Exception thrown when a query fails.
 *
 * @package    Framework
 * @subpackage Exceptions
 * @since      1.0.0
 */
namespace Kirki\Framework\Exceptions;

\defined('ABSPATH') || exit;
class UniqueConstraintViolationException extends QueryException
{
    //
}
