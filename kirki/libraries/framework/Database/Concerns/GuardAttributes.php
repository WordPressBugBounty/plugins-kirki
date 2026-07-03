<?php

/**
 * Trait implementing mass-assignment protection via fillable and guarded lists.
 * Supports global unguard for seeding and per-model fillable configuration.
 * Prevents accidental writes to protected columns during bulk attribute sets.
 *
 * @package    Framework
 * @subpackage Database\Concerns
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Concerns;

\defined('ABSPATH') || exit;
use Kirki\Framework\Database\Connection\Connection;
use Kirki\Framework\Supports\Facades\Schema;
use function Kirki\Framework\Polyfill\str_contains;
use function Kirki\Framework\Polyfill\str_starts_with;
trait GuardAttributes
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $fillable = [];
    /**
     * The attributes that should be protected from mass assignment.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $guarded = ['*'];
    /**
     * Indicates if all mass assignment is allowed.
     *
     * @var bool
     *
     * @since 1.0.0
     */
    protected static $unguarded = \false;
    /**
     * The guardable columns for the model.
     *
     * @var array<class-string, list<string>>
     *
     * @since 1.0.0
     */
    protected static $guardable_columns = [];
    /**
     * Get the connection for the model.
     *
     * @return Connection
     *
     * @since 1.0.0
     */
    protected static abstract function get_connection();
    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function get_fillable()
    {
        return $this->fillable;
    }
    /**
     * Set the fillable attributes for the model.
     *
     * @param array $fillable The fillable.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function fillable(array $fillable)
    {
        $this->fillable = $fillable;
        return $this;
    }
    /**
     * Merge the given fillable attributes with the existing fillable attributes.
     *
     * @param array $fillable The fillable.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function merge_fillable(array $fillable)
    {
        if ($fillable === []) {
            return $this;
        }
        $this->fillable = \array_values(\array_unique(\array_merge($this->fillable, $fillable)));
        return $this;
    }
    /**
     * Get the guarded attributes for the model.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function get_guarded()
    {
        return static::$unguarded ? [] : $this->guarded;
    }
    /**
     * Set the guarded attributes for the model.
     *
     * @param array $guarded The guarded.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function guard(array $guarded)
    {
        $this->guarded = $guarded;
        return $this;
    }
    /**
     * Merge the given guarded attributes with the existing guarded attributes.
     *
     * @param array $guarded The guarded.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function merge_guarded(array $guarded)
    {
        $this->guarded = \array_values(\array_unique(\array_merge($this->guarded, $guarded)));
        return $this;
    }
    /**
     * Enable mass assignment for all attributes.
     *
     * @param mixed $state The state.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function unguard($state = \true)
    {
        static::$unguarded = $state;
    }
    /**
     * Disable mass assignment for all attributes.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function reguard()
    {
        static::$unguarded = \false;
    }
    /**
     * Determine if mass assignment is enabled for all attributes.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public static function is_unguarded()
    {
        return static::$unguarded;
    }
    /**
     * Execute the given callback while mass assignment is enabled for all attributes.
     *
     * @template TReturn
     * 
     * @param (callable(): TReturn) $callback The callback to invoke.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public static function unguarded(callable $callback)
    {
        if (static::$unguarded) {
            return $callback();
        }
        static::unguard();
        try {
            return $callback();
        } finally {
            static::reguard();
        }
    }
    /**
     * Determine if the given key is fillable.
     *
     * @param mixed $key The key.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function is_fillable($key)
    {
        if (static::$unguarded) {
            return \true;
        }
        if (\in_array($key, $this->get_fillable(), \true)) {
            return \true;
        }
        if ($this->is_guarded($key)) {
            return \false;
        }
        return empty($this->get_fillable()) && !str_contains($key, '.') && !str_starts_with($key, '_');
    }
    /**
     * Determine if the given key is guarded.
     *
     * @param mixed $key The key.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function is_guarded($key)
    {
        if (empty($this->get_guarded())) {
            return \false;
        }
        return $this->get_guarded() === ['*'] || !empty(\preg_grep('/^' . \preg_quote($key, '/') . '$/i', $this->get_guarded())) || !$this->is_guardable_column($key);
    }
    /**
     * Determine if the given key is guardable.
     *
     * @param mixed $key The key.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function is_guardable_column($key)
    {
        if ($this->has_set_mutator($key) || $this->is_class_castable($key)) {
            return \true;
        }
        if (!isset(static::$guardable_columns[\get_class($this)])) {
            $columns = Schema::get_column_listing($this->get_table());
            if (empty($columns)) {
                return \true;
            }
            static::$guardable_columns[\get_class($this)] = $columns;
        }
        return \in_array($key, static::$guardable_columns[\get_class($this)]);
    }
    /**
     * Determine if the model is totally guarded.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function totally_guarded()
    {
        return \count($this->get_fillable()) === 0 && $this->get_guarded() == ['*'];
    }
    /**
     * Get the fillable attributes from the array.
     *
     * @param array $attributes The attributes.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function fillable_from_array(array $attributes)
    {
        if (\count($this->get_fillable()) > 0 && !static::$unguarded) {
            return \array_intersect_key($attributes, \array_flip($this->get_fillable()));
        }
        return $attributes;
    }
}
