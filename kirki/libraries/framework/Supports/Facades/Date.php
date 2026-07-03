<?php

/**
 * Facade proxy for DateManager exposing native DateTime date operations.
 * Primary date/time entry point for application code.
 *
 * @package    Framework
 * @subpackage Supports\Facades
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports\Facades;

\defined('ABSPATH') || exit;
use Kirki\Framework\Facade;
// phpcs:disable Generic.Files.LineLength.TooLong
/**
 * Facade proxy for DateManager exposing native DateTime date operations.
 *
 * @method static \DateTime now(\DateTimeZone|string|int|null $timezone = null)
 * @method static \DateTime parse(\DateTimeInterface|string|int|float|null $time, \DateTimeZone|string|int|null $timezone = null)
 * @method static \DateTime instance(\DateTimeInterface $date)
 * @method static \DateTime|null create_from_format(string $format, string $time, \DateTimeZone|string|int|null $timezone = null)
 * @method static \DateTime create_from_timestamp(string|int|float $timestamp, \DateTimeZone|string|int|null $timezone = null)
 * @method static bool is_valid_date(mixed $value)
 * @method static \DateTime start_of_day(\DateTimeInterface $date)
 * @method static bool is_after(\DateTimeInterface $date, \DateTimeInterface $other)
 * @method static string to_sql_datetime_string(\DateTimeInterface $date)
 * @see    Framework\Managers\DateManager
 */
class Date extends Facade
{
    /**
     * Get the accessor.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public static function get_accessor()
    {
        return 'date';
    }
}
