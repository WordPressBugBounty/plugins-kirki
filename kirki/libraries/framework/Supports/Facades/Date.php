<?php

/**
 * Facade proxy for Somoy exposing static date and time factories.
 * Primary date/time entry point for application code.
 *
 * @package    Framework
 * @subpackage Supports\Facades
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports\Facades;

\defined('ABSPATH') || exit;
use Kirki\Framework\Contracts\SomoyInterface;
use Kirki\Framework\Facade;
use Kirki\Framework\Supports\Somoy;
// phpcs:disable Generic.Files.LineLength.TooLong
/**
 * Facade proxy for Somoy exposing static date and time factories.
 *
 * @method static \Framework\Contracts\SomoyInterface now(\DateTimeZone|string|null $timezone = null)
 * @method static \Framework\Contracts\SomoyInterface today(\DateTimeZone|string|null $timezone = null)
 * @method static \Framework\Contracts\SomoyInterface yesterday(\DateTimeZone|string|null $timezone = null)
 * @method static \Framework\Contracts\SomoyInterface tomorrow(\DateTimeZone|string|null $timezone = null)
 * @method static \Framework\Contracts\SomoyInterface parse(\DateTimeInterface|string|int|float|null $time = null, \DateTimeZone|string|null $timezone = null)
 * @method static \Framework\Contracts\SomoyInterface instance(\DateTimeInterface $date)
 * @method static \Framework\Contracts\SomoyInterface create_from_timestamp(int|float|string $timestamp, \DateTimeZone|string|null $timezone = null)
 * @method static \Framework\Contracts\SomoyInterface create_from_format(string $format, string $time, \DateTimeZone|string|null $timezone = null)
 * @method static \Framework\Contracts\SomoyInterface create(int|null $year = null, int|null $month = null, int|null $day = null, int|null $hour = null, int|null $minute = null, int|null $second = null, \DateTimeZone|string|null $timezone = null)
 * @method static bool is_valid_date(mixed $value)
 * @see    \Framework\Supports\Somoy
 * @see    \Framework\Contracts\SomoyInterface
 */
class Date extends Facade
{
    /**
     * Get the accessor.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function get_accessor()
    {
        return SomoyInterface::class;
    }
    /**
     * Forward static calls to Somoy.
     *
     * @param string $method The method name being called.
     * @param array $arguments The arguments passed to the method.
     *
     * @return mixed The result of the underlying method call.
     *
     * @since 1.0.0
     */
    public static function __callStatic($method, $arguments)
    {
        return Somoy::$method(...$arguments);
    }
}
