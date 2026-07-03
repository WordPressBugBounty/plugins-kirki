<?php

/**
 * Runtime exception indicating a database query found no matching record.
 * Raised by singular retrieval helpers when zero rows are returned.
 * Distinct from ModelNotFoundException for lower-level query operations.
 *
 * @package    Framework
 * @subpackage Exceptions
 * @since      1.0.0
 */
namespace Kirki\Framework\Exceptions;

\defined('ABSPATH') || exit;
use Exception;
use RuntimeException;
class RecordNotFoundException extends RuntimeException
{
    //
}
