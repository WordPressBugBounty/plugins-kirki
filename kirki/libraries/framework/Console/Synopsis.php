<?php

/**
 * Value object describing WP-CLI command arguments, options, and positional parameters.
 * Parses synopsis arrays into normalized CLI signatures for CommandBase implementations.
 * Keeps command registration consistent across the console layer.
 *
 * @package    Framework
 * @subpackage Console
 * @since      1.0.0
 */
namespace Kirki\Framework\Console;

\defined('ABSPATH') || exit;
use Kirki\Framework\Supports\Flex;
/**
 * Synopsis class to describe WP-CLI command arguments, options, and positional parameters.
 * 
 * @method $this name(string $name)
 * @method $this description(string $description)
 * @method $this optional()
 * @method $this repeating()
 * @method $this options(array $options)
 * @method $this type('positional'|'assoc'|'flag' $type)
 * @method $this default($value)
 */
class Synopsis extends Flex
{
    /**
     * Handle dynamic method calls
     *
     * @param mixed $method The method name.
     * @param mixed $parameters The parameters array.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static())->{$method}(...$parameters);
    }
}
