<?php

/**
 * Abstract API resource transformer that maps models or arrays to public response shapes.
 * Offers static make, collection, and paginated helpers for batch serialization.
 * Delegates property access to the underlying resource via magic methods.
 *
 * @package Framework
 * @since   1.0.0
 */
namespace Kirki\Framework;

\defined('ABSPATH') || exit;
use Kirki\Framework\Contracts\Support\Arrayable;
use Kirki\Framework\Contracts\Support\Jsonable;
use Kirki\Framework\Collections\Collection;
use Kirki\Framework\Database\Query\Paginator;
use Kirki\Framework\Supports\Arr;
use JsonSerializable;
abstract class Resource implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * The resource instance.
     *
     * @var object|array
     *
     * @since 1.0.0
     */
    protected $resource;
    /**
     * Create a new resource instance.
     *
     * @param object|array $resource The resource to create a new instance of.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct($resource)
    {
        if (\is_array($resource)) {
            $this->resource = (object) $resource;
        } else {
            $this->resource = $resource;
        }
    }
    /**
     * Convert the resource to an array.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public abstract function to_array();
    /**
     * Create a new resource instance, or return null if the resource is null.
     *
     * @param mixed $parameters The resource to create a new instance of.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function make(...$parameters)
    {
        return (new static(...$parameters))->to_array();
    }
    /**
     * Converts an iterable of resources into an array of resource representations.
     *
     * This method loops over the iterable and creates a new instance of the resource
     * class for each item, then calls the to_array method on the resource to
     * obtain its representation as an array.
     *
     * @param mixed    $parameters The parameters to pass to the resource constructor.
     *
     * @return array The array of resource representations.
     *
     * @since 1.0.0
     */
    public static function collection(...$parameters)
    {
        if (empty($parameters)) {
            return [];
        }
        $resource = \array_shift($parameters);
        if ($resource instanceof Collection) {
            $resource = $resource->all();
        }
        return Arr::map($resource, fn($resource) => (new static($resource, ...$parameters))->to_array());
    }
    /**
     * Converts a paginator object into an array of resource representations,
     * including pagination metadata.
     *
     * This method loops over the paginator's results and creates a new instance
     * of the resource class for each item, then calls the to_array method on
     * the resource to obtain its representation as an array.
     *
     * @param Paginator $paginator The paginator object to convert.
     * @param mixed    $parameters The parameters to pass to the resource constructor.
     *
     * @return array The array of resource representations, including pagination metadata.
     *
     * @since 1.0.0
     */
    public static function paginated(Paginator $paginator, ...$parameters)
    {
        $paginated_data = $paginator->to_array();
        foreach ($paginated_data['results'] as $key => $resource) {
            $paginated_data['results'][$key] = (new static($resource, ...$parameters))->to_array();
        }
        return $paginated_data;
    }
    /**
     * Convert the resource to a JSON string.
     *
     * Encodes the array form of the resource for straightforward transport or
     * logging purposes.
     *
     * @param mixed $options The options array.
     *
     * @return string The JSON-encoded paginator representation
     *
     * @since 1.0.0
     */
    public function to_json($options = 0)
    {
        return Arr::json_encode($this->to_array(), $options);
    }
    /**
     * Check if a property exists on the underlying resource.
     *
     * This magic method allows you to check if a property exists on the underlying resource
     * as if it were a property of the current class. This provides a convenient way of
     * checking for the existence of resource properties without having to explicitly call a method.
     *
     * @param string $name The name of the property to check.
     *
     * @return bool True if the property exists, false otherwise.
     *
     * @since 1.0.0
     */
    public function __isset($name)
    {
        return isset($this->resource->{$name});
    }
    /**
     * Convert the resource to a JSON string.
     *
     * @return array The JSON-encoded resource representation
     *
     * @since 1.0.0
     */
    public function jsonSerialize() : array
    {
        return $this->to_array();
    }
    /**
     * Dynamically pass properties of the underlying resource to the caller.
     *
     * This magic method allows you to access properties of the underlying resource
     * as if they were properties of the current class. This provides a convenient
     * way of accessing resource properties without having to explicitly call a method.
     *
     * @param string $name The name of the property to access.
     *
     * @return mixed The value of the accessed property.
     *
     * @since 1.0.0
     */
    public function __get($name)
    {
        return $this->resource->{$name} ?? null;
    }
    /**
     * Dynamically pass properties of the underlying resource to the caller.
     *
     * This magic method allows you to access properties of the underlying resource
     * as if they were properties of the current class. This provides a convenient
     * way of accessing resource properties without having to explicitly call a method.
     *
     * @param string $name The name of the property to access.
     * @param mixed $value The value to set.
     *
     * @return $this The current instance.
     *
     * @since 1.0.0
     */
    public function __set($name, $value)
    {
        $this->resource->{$name} = $value;
        return $this;
    }
    /**
     * Dynamically pass method calls of the underlying resource to the caller.
     *
     * This magic method allows you to call methods of the underlying resource
     * as if they were methods of the current class. This provides a convenient
     * way of accessing resource methods without having to explicitly call a method.
     *
     * @param string $method The name of the method to access.
     * @param array $args The arguments to pass to the method.
     *
     * @return mixed The return value of the accessed method.
     *
     * @since 1.0.0
     */
    public function __call($method, $args)
    {
        return $this->resource->{$method}(...$args);
    }
}
