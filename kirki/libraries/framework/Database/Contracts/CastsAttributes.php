<?php

/**
 * Interface for custom attribute cast classes with get and set methods.
 * Receives the model, key, value, and full attributes array for bidirectional transformation.
 * Used in model $casts for complex types like JSON or enums.
 *
 * @package    Framework
 * @subpackage Database\Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Contracts;

\defined('ABSPATH') || exit;
use Kirki\Framework\Database\Query\Model;
interface CastsAttributes
{
    /**
     * Get the attribute value.
     *
     * @param Model $model The model instance.
     * @param string $key The attribute key.
     * @param TGet|null $value The attribute value.
     * @param array $attributes The attributes array.
     *
     * @return mixed The attribute value.
     *
     * @since 1.0.0
     */
    public function get(Model $model, string $key, $value, array $attributes);
    /**
     * Set the attribute value.
     *
     * @param Model $model The model instance.
     * @param string $key The attribute key.
     * @param TSet|null $value The attribute value.
     * @param array $attributes The attributes array.
     *
     * @return mixed The attribute value.
     *
     * @since 1.0.0
     */
    public function set(Model $model, string $key, $value, array $attributes);
}
