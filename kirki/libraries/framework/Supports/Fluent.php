<?php

/**
 * Lightweight dynamic object bag implementing ArrayAccess, JsonSerializable, and iteration.
 * Stores arbitrary key-value attributes with fill, get, and fluent access patterns.
 * Alternative to stdClass for structured but schema-free data.
 *
 * @package    Framework
 * @subpackage Supports
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports;

\defined('ABSPATH') || exit;
use ArrayAccess;
use ArrayIterator;
use Kirki\Framework\Contracts\Support\Arrayable;
use Kirki\Framework\Contracts\Support\Jsonable;
use Kirki\Framework\Supports\Traits\Macroable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;
use function Kirki\Framework\deep_get;
use function Kirki\Framework\deep_set;
use function Kirki\Framework\Polyfill\array_first;
use function Kirki\Framework\value;
class Fluent implements ArrayAccess, IteratorAggregate, Arrayable, Jsonable, JsonSerializable
{
    use Macroable {
        __call as macro_call;
    }
    /**
     * The attributes.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $attributes = [];
    /**
     * Create a new instance.
     *
     * @param array $attributes The attributes array.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }
    /**
     * Create a new instance.
     *
     * @param array $attributes The attributes array.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public static function make(array $attributes = [])
    {
        return new static($attributes);
    }
    /**
     * Fill the attributes with the given array.
     *
     * @param array $attributes The attributes array.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }
    /**
     * Get the value of a given attribute.
     *
     * @param string $key The attribute key.
     * @param mixed $default The default value if the attribute does not exist.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function value($key, $default = null)
    {
        if ($this->exists($key)) {
            return $this->get($key);
        }
        return value($default);
    }
    /**
     * Get the keys of the attributes.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function keys()
    {
        return \array_keys($this->attributes);
    }
    /**
     * Get the value of a given attribute.
     *
     * @param string $key The attribute key.
     * @param mixed $default The default value if the attribute does not exist.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function get($key, $default = null)
    {
        return deep_get($this->attributes, $key, $default);
    }
    /**
     * Check if a given attribute exists.
     *
     * @param string $key The attribute key.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function exists($key)
    {
        return \array_key_exists($key, $this->attributes);
    }
    /**
     * Set the value of a given attribute.
     *
     * @param string $key The attribute key.
     * @param mixed $value The value to set.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function set($key, $value)
    {
        deep_set($this->attributes, $key, $value);
        return $this;
    }
    /**
     * Remove a given attribute.
     *
     * @param string $key The attribute key.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function remove($key)
    {
        if ($this->exists($key)) {
            unset($this->attributes[$key]);
        }
        return $this;
    }
    /**
     * Get the value of a given attribute.
     *
     * @param string $keys The attribute keys.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function all($keys = null)
    {
        $data = $this->data();
        if (!$keys) {
            return $data;
        }
        $results = [];
        $keys = \is_array($keys) ? $keys : \func_get_args();
        foreach ($keys as $key) {
            Arr::set($results, $key, Arr::get($data, $key));
        }
        return $results;
    }
    /**
     * Get the value of a given attribute.
     *
     * @param string $key The attribute key.
     * @param mixed $default The default value if the attribute does not exist.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function data($key = null, $default = null)
    {
        return $this->get($key, $default);
    }
    /**
     * Get the attributes.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function get_attributes()
    {
        return $this->attributes;
    }
    /**
     * Check if the attributes are empty.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function empty() : bool
    {
        return empty($this->attributes);
    }
    /**
     * Check if the attributes are not empty.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function not_empty() : bool
    {
        return !$this->empty();
    }
    /**
     * Get all attributes as an array.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function to_array()
    {
        return $this->attributes;
    }
    /**
     * Magic getter for attributes.
     *
     * @param string $name The attribute name.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function __get($name)
    {
        return $this->get($name);
    }
    /**
     * Magic setter for attributes.
     *
     * @param string $name The attribute name.
     * @param mixed $value The value to set.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }
    /**
     * Magic isset to check if an attribute exists.
     *
     * @param string $name The attribute name.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function __isset($name)
    {
        return $this->exists($name);
    }
    /**
     * Magic unset to remove an attribute.
     *
     * @param string $name The attribute name.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __unset($name)
    {
        unset($this->attributes[$name]);
    }
    /**
     * Determine if the given offset exists.
     *
     * @param mixed $offset The offset.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function offsetExists($offset) : bool
    {
        return isset($this->attributes[$offset]);
    }
    /**
     * OffsetGet.
     *
     * @param mixed $offset The offset.
     *
     * @return void
     *
     * @since 1.0.0
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->value($offset);
    }
    /**
     * Set the value at the given offset.
     *
     * @param mixed $offset The offset.
     * @param mixed $value The value.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function offsetSet($offset, $value) : void
    {
        $this->attributes[$offset] = $value;
    }
    /**
     * Unset the value at the given offset.
     *
     * @param mixed $offset The offset.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function offsetUnset($offset) : void
    {
        unset($this->attributes[$offset]);
    }
    /**
     * Get an iterator for the attributes.
     * 
     * @template TKey
     * @template TValue
     *
     * @return ArrayIterator<TKey, TValue>
     *
     * @since 1.0.0
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator($this->attributes);
    }
    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function jsonSerialize() : array
    {
        return $this->to_array();
    }
    /**
     * Convert the object to a JSON string.
     *
     * @param mixed $options The options array.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function to_json($options = 0)
    {
        return Arr::json_encode($this->jsonSerialize(), $options);
    }
    /**
     * Magic call to set an attribute by method name.
     *
     * @param string $method The method name (attribute key).
     * @param array $arguments The arguments to set as value.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function __call($method, $arguments)
    {
        if (static::has_macro($method)) {
            return $this->macro_call($method, $arguments);
        }
        $this->attributes[$method] = \count($arguments) > 0 ? array_first($arguments) : \true;
        return $this;
    }
}
