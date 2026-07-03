<?php

/**
 * Interface requiring to_array for objects that can be flattened to associative arrays.
 * Used by DTOs, resources, and collections for serialization.
 * Foundation for JSON encoding and API responses.
 *
 * @package    Framework
 * @subpackage Contracts\Support
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts\Support;

\defined('ABSPATH') || exit;
interface Arrayable
{
    /**
     * Convert the object to an array.
     *
     * @return array The array representation of the object
     *
     * @since 1.0.0
     */
    public function to_array();
}
