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
use Exception;
use Throwable;
class QueryException extends Exception
{
    /**
     * The sql.
     *
     * @var mixed
     *
     * @since 1.0.0
     */
    protected $sql;
    /**
     * The bindings.
     *
     * @var mixed
     *
     * @since 1.0.0
     */
    protected $bindings;
    /**
     * Create a new instance.
     *
     * @param mixed $sql The sql.
     * @param array $bindings The bindings.
     * @param Throwable $error The error.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct($sql, array $bindings, Throwable $error)
    {
        $this->sql = $sql;
        $this->bindings = $bindings;
        $this->code = $error->getCode();
        $this->message = $this->format_message($sql, $bindings, $error);
    }
    /**
     * Format message.
     *
     * @param mixed $sql The sql.
     * @param mixed $bindings The bindings.
     * @param Throwable $error The error.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function format_message($sql, $bindings, Throwable $error)
    {
        return $error->getMessage() . ' (Connection: MySQL, SQL: ' . $this->prepare($sql, $bindings) . ')';
    }
    /**
     * Get the sql.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function get_sql()
    {
        return $this->sql;
    }
    /**
     * Get the bindings.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function get_bindings()
    {
        return $this->bindings;
    }
    /**
     * Prepare.
     *
     * @param mixed $sql The sql.
     * @param mixed $bindings The bindings.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function prepare($sql, $bindings)
    {
        $count = 1;
        foreach ($bindings as $binding) {
            $sql = \str_replace('?', $binding, $sql, $count);
        }
        return $sql;
    }
}
