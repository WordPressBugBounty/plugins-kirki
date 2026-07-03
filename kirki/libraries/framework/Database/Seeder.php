<?php

/**
 * Base class for database seeders with call tracking to prevent duplicate execution.
 * Resolves seeder classes from the container and runs them within optional transactions.
 * Coordinates ordered seeding through the static call API.
 *
 * @package    Framework
 * @subpackage Database
 * @since      1.0.0
 */
namespace Kirki\Framework\Database;

\defined('ABSPATH') || exit;
use Kirki\Framework\Supports\Arr;
use Kirki\Framework\Supports\Facades\DB;
use Kirki\Framework\Supports\Facades\Log;
use Throwable;
use function Kirki\Framework\app;
class Seeder
{
    /**
     * Store the called seeder classes
     *
     * @var string[]
     *
     * @since 1.0.0
     */
    protected static $called = [];
    /**
     * Track the already resolved seeders so that it doesn't run again
     *
     * @var string[]
     *
     * @since 1.0.0
     */
    protected static $resolved = [];
    /**
     * Call the seeders
     *
     * @param mixed $class The class.
     *
     * @return Seeder
     *
     * @since 1.0.0
     */
    public function call($class)
    {
        $classes = Arr::wrap($class);
        foreach ($classes as $class) {
            if (!\in_array($class, static::$called, \true) && empty(static::$resolved[$class])) {
                static::$called[] = $class;
            }
        }
        return $this;
    }
    /**
     * Resolve the seeder
     *
     * @param mixed $class The class.
     *
     * @return Seeder
     *
     * @since 1.0.0
     */
    protected function resolve($class)
    {
        return app()->make($class);
    }
    /**
     * Run the seeder
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function run()
    {
        //
    }
    /**
     * Run the seeders
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __invoke()
    {
        try {
            while (!empty(static::$called)) {
                $seeder = \array_shift(static::$called);
                $this->resolve($seeder)->run();
                static::$resolved[$seeder] = \true;
            }
        } catch (Throwable $exception) {
            throw $exception;
        }
    }
}
