<?php

/**
 * Trait managing created_at and updated_at columns on models.
 * Provides fresh timestamp helpers and hooks into save lifecycle for automatic timestamp writes.
 * Can be disabled per model via the timestamps property.
 *
 * @package    Framework
 * @subpackage Database\Concerns
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Concerns;

\defined('ABSPATH') || exit;
use Kirki\Framework\Supports\Facades\Date;
trait HasTimestamps
{
    /**
     * Convert a value to a date time string for storage.
     *
     * @param mixed $value The value to convert
     *
     * @return mixed The formatted date time string or the original empty value
     *
     * @since 1.0.0
     */
    protected abstract function from_date_time($value);
    /**
     * Indicates if the model should automatically manage created_at and updated_at timestamps.
     *
     * @var bool
     *
     * @since 1.0.0
     */
    protected $timestamps = \true;
    /**
     * Determine if the model uses timestamps.
     *
     * @return bool True when the model uses timestamps; false otherwise
     *
     * @since 1.0.0
     */
    public function uses_timestamps()
    {
        return $this->timestamps;
    }
    /**
     * Get the current timestamp for a model instance.
     *
     * @return \DateTime The current timestamp
     *
     * @since 1.0.0
     */
    public function fresh_timestamp()
    {
        return Date::now();
    }
    /**
     * Get the current timestamp as a string.
     *
     * @return string The current timestamp as a string
     *
     * @since 1.0.0
     */
    public function fresh_timestamp_string()
    {
        return $this->from_date_time($this->fresh_timestamp());
    }
    /**
     * Get the name of the "created at" column.
     *
     * @return string|null The name of the "created at" column
     *
     * @since 1.0.0
     */
    public function get_created_at_column()
    {
        $class = static::class;
        if (\defined($class . '::CREATED_AT')) {
            return \constant($class . '::CREATED_AT');
        }
        return 'created_at';
    }
    /**
     * Get the name of the "updated at" column.
     *
     * @return string|null The name of the "updated at" column
     *
     * @since 1.0.0
     */
    public function get_updated_at_column()
    {
        $class = static::class;
        if (\defined($class . '::UPDATED_AT')) {
            return \constant($class . '::UPDATED_AT');
        }
        return 'updated_at';
    }
    /**
     * Set the value of the "created at" column.
     *
     * @param mixed $value The value to set
     *
     * @return $this The model instance for method chaining
     *
     * @since 1.0.0
     */
    public function set_created_at($value)
    {
        $this->{$this->get_created_at_column()} = $value;
        return $this;
    }
    /**
     * Set the value of the "updated at" column.
     *
     * @param mixed $value The value to set
     *
     * @return $this The model instance for method chaining
     *
     * @since 1.0.0
     */
    public function set_updated_at($value)
    {
        $this->{$this->get_updated_at_column()} = $value;
        return $this;
    }
    /**
     * Get the qualified name of the "updated at" column.
     *
     * @return string|null The qualified name of the "updated at" column
     *
     * @since 1.0.0
     */
    public function get_qualified_updated_at_column()
    {
        $column = $this->get_updated_at_column();
        return $column ? $this->prepare_column($column) : null;
    }
    /**
     * Get the qualified name of the "created at" column.
     *
     * @return string|null The qualified name of the "created at" column
     *
     * @since 1.0.0
     */
    public function get_qualified_created_at_column()
    {
        $column = $this->get_created_at_column();
        return $column ? $this->prepare_column($column) : null;
    }
}
