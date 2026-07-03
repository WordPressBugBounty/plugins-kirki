<?php

/**
 * Runtime exception thrown when a query expecting a single row returns more than one.
 * Carries the matched record count for error reporting.
 * Used by firstOrFail-style retrieval methods on the query builder.
 *
 * @package    Framework
 * @subpackage Exceptions
 * @since      1.0.0
 */
namespace Kirki\Framework\Exceptions;

\defined('ABSPATH') || exit;
use RuntimeException;
class MultipleRecordsFoundException extends RuntimeException
{
    /**
     * The count.
     *
     * @var mixed
     *
     * @since 1.0.0
     */
    protected $count;
    /**
     * Create a new instance.
     *
     * @param mixed $count The count.
     * @param mixed $code The code.
     * @param mixed $previous The previous.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct($count, $code = 0, $previous = null)
    {
        $this->count = $count;
        parent::__construct(\sprintf('%d records were found', $count, $code, $previous));
    }
    /**
     * Get the count.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function get_count()
    {
        return $this->count;
    }
}
