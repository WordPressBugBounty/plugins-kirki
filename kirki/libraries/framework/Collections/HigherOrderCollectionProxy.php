<?php

/**
 * Provides property-style access on collection items by delegating to a named collection method such as pluck or map.
 * Lets callers write concise syntax like $collection->pluck->name instead of wrapping closures manually.
 * Returns the transformed collection result for each accessed key.
 *
 * @package    Framework
 * @subpackage Collections
 * @since      1.0.0
 */
namespace Kirki\Framework\Collections;

\defined('ABSPATH') || exit;
class HigherOrderCollectionProxy
{
    /**
     * The collection instance.
     *
     * @var Collection
     *
     * @since 1.0.0
     */
    protected $collection;
    /**
     * The method to call on the collection.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $method;
    /**
     * Create a new higher order collection proxy instance.
     *
     * @param Collection $collection The collection instance.
     * @param string $method The method to call on the collection.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct(Collection $collection, string $method)
    {
        $this->collection = $collection;
        $this->method = $method;
    }
    /**
     * Get the value of the property.
     *
     * @param string $key The key of the property.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function __get($key)
    {
        return $this->collection->{$this->method}(function ($value) use($key) {
            return \is_array($value) ? $value[$key] : $value->{$key};
        });
    }
    /**
     * Call the method on the collection.
     *
     * @param string $method The method to call on the collection.
     * @param array $parameters The parameters to pass to the method.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function __call($method, $parameters)
    {
        return $this->collection->{$this->method}(function ($value) use($method, $parameters) {
            return \is_string($value) ? $value::$method(...$parameters) : $value->{$method}(...$parameters);
        });
    }
}
