<?php

/**
 * Interface requiring to_json for objects that produce JSON string representations.
 * Accepts optional encoding flags.
 * Paired with Arrayable across DTOs and API resource classes.
 *
 * @package    Framework
 * @subpackage Contracts\Support
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts\Support;

\defined('ABSPATH') || exit;
interface Jsonable
{
    /**
     * Convert the object to a JSON string.
     *
     * @param mixed $options The options array.
     *
     * @return string The JSON representation of the object
     *
     * @since 1.0.0
     */
    public function to_json($options = 0);
}
