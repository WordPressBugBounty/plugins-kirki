<?php

/**
 * Defines the service container API for binding, singletons, aliases, and resolution.
 * Mirrors container operations adapted for PHP 7.4.
 * Implemented by Application and used wherever dependency injection is required.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
use Closure;
interface Container
{
    /**
     * Bind a class to the container.
     *
     * @param string $name The name.
     * @param Closure $resolver The resolver closure.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function bind(string $name, Closure $resolver);
    /**
     * Bind a class to the container as a singleton (lazy loaded).
     *
     * @param string $name The name.
     * @param Closure $resolver The resolver closure.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function singleton(string $name, Closure $resolver);
    /**
     * Bind an existing instance to the container.
     *
     * @param string $name The name.
     * @param mixed $instance The service instance.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function instance(string $name, $instance);
    /**
     * Create an alias for a service.
     *
     * @param string $alias The alias name.
     * @param string $abstract The abstract service name.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function alias(string $alias, string $abstract);
    /**
     * Tag services for grouped resolution.
     *
     * @param array $services The services to tag.
     * @param string $tag The tag name.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function tag(array $services, string $tag);
    /**
     * Get all services with a given tag.
     *
     * @param string $tag The tag name.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function tagged(string $tag) : array;
    /**
     * Make a class from the container.
     *
     * @param string $name The name.
     * @param array $parameters The parameters array.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function make(string $name, array $parameters = []);
    /**
     * Check if a class exists in the container.
     *
     * @param string $name The name.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function has(string $name) : bool;
    /**
     * Flush all bindings and instances.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function flush();
}
