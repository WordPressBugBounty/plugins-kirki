<?php

/**
 * Minimal interface for transforming a single attribute value on read.
 * Implementors define get to coerce or decode stored data.
 * Lighter-weight alternative to full model casts for DTOs and simple transformers.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface CastAttribute
{
    /**
     * Get the value of the attribute.
     *
     * @param mixed $value The value to cast.
     *
     * @return mixed The casted value.
     *
     * @since 1.0.0
     */
    public function get($value);
}
