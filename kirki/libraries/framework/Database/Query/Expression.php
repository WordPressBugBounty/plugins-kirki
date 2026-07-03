<?php

/**
 * Value object wrapping raw SQL fragments that should not be quoted by the query compiler.
 * Passed to select, where, and order clauses when database functions or literals are needed.
 * Prevents accidental parameter binding of SQL expressions.
 *
 * @package    Framework
 * @subpackage Database\Query
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Query;

\defined('ABSPATH') || exit;
class Expression
{
    /**
     * The value.
     *
     * @var mixed
     *
     * @since 1.0.0
     */
    protected $value;
    /**
     * Create a new Expression instance
     *
     * @param string|int|float $value The value to wrap in the expression
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
    /**
     * Get the value of the expression
     *
     * @return string|int|float
     *
     * @since 1.0.0
     */
    public function get_value()
    {
        return $this->value;
    }
    /**
     * Get the string representation of the expression
     *
     * @return string|int|float
     *
     * @since 1.0.0
     */
    public function __toString()
    {
        return $this->get_value();
    }
}
