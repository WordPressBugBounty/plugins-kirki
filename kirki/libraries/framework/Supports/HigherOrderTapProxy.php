<?php

/**
 * Proxy that invokes methods on a target object and always returns the target for chaining.
 * Powers the tap helper pattern where side-effect calls should not break fluent pipelines.
 * Delegates __call to the wrapped instance.
 *
 * @package    Framework
 * @subpackage Supports
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports;

\defined('ABSPATH') || exit;
class HigherOrderTapProxy
{
    /**
     * The target instance.
     *
     * @var object
     *
     * @since 1.0.0
     */
    protected $target;
    /**
     * Create a new proxy instance.
     *
     * @param mixed $target The target.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct($target)
    {
        $this->target = $target;
    }
    /**
     * Dynamically handle calls to the object.
     *
     * @param mixed $method The method name.
     * @param mixed $parameters The parameters array.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function __call($method, $parameters)
    {
        $this->target->{$method}(...$parameters);
        return $this->target;
    }
}
