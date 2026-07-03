<?php

/**
 * Date and time operations using native PHP DateTime.
 * Centralizes date/time operations with WordPress timezone awareness.
 *
 * @package    Framework
 * @subpackage Managers
 * @since      1.0.0
 */
namespace Kirki\Framework\Managers;

\defined('ABSPATH') || exit;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Kirki\Framework\Constants\DateTimeFormats;
class DateManager
{
    /**
     * Default SQL datetime format string.
     *
     * @var string
     *
     * @since 1.0.0
     */
    public const BASE_FORMAT = DateTimeFormats::DB_DATETIME;
    /**
     * Get the current date and time.
     *
     * @param \DateTimeZone|string|int|null $timezone Optional timezone.
     *
     * @return \DateTime
     *
     * @since 1.0.0
     */
    public function now($timezone = null)
    {
        return new DateTime('now', $this->resolve_timezone($timezone));
    }
    /**
     * Parse a value into a DateTime instance.
     *
     * @param \DateTimeInterface|string|int|float|null $time The value to parse.
     * @param \DateTimeZone|string|int|null $timezone Optional timezone.
     *
     * @return \DateTime
     *
     * @since 1.0.0
     */
    public function parse($time, $timezone = null)
    {
        $resolved_timezone = $this->resolve_timezone($timezone);
        if ($time instanceof DateTimeInterface) {
            $date = $this->to_datetime($time);
            return $date->setTimezone($resolved_timezone);
        }
        if (\is_int($time) || \is_float($time)) {
            return $this->create_from_timestamp($time, $resolved_timezone);
        }
        return new DateTime((string) $time, $resolved_timezone);
    }
    /**
     * Create a DateTime instance from an existing date object.
     *
     * @param \DateTimeInterface $date The source date.
     *
     * @return \DateTime
     *
     * @since 1.0.0
     */
    public function instance(DateTimeInterface $date)
    {
        return $this->to_datetime($date);
    }
    /**
     * Create a DateTime instance from a format string.
     *
     * @param string $format The expected format.
     * @param string $time The date string.
     * @param \DateTimeZone|string|int|null $timezone Optional timezone.
     *
     * @return \DateTime|null
     *
     * @since 1.0.0
     */
    public function create_from_format($format, $time, $timezone = null)
    {
        $date = DateTime::createFromFormat($format, $time, $this->resolve_timezone($timezone));
        if ($date === \false) {
            return null;
        }
        return $date;
    }
    /**
     * Create a DateTime instance from a Unix timestamp.
     *
     * @param string|int|float $timestamp The Unix timestamp.
     * @param \DateTimeZone|string|int|null $timezone Optional timezone.
     *
     * @return \DateTime
     *
     * @since 1.0.0
     */
    public function create_from_timestamp($timestamp, $timezone = null)
    {
        $date = DateTime::createFromFormat('U', (string) $timestamp);
        return $date->setTimezone($this->resolve_timezone($timezone));
    }
    /**
     * Check if the value is a valid date.
     *
     * @param mixed $value The value to validate.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function is_valid_date($value)
    {
        if ($value instanceof DateTimeInterface) {
            return \true;
        }
        if (!\is_string($value) && !\is_numeric($value)) {
            return \false;
        }
        try {
            $this->parse($value);
            return \true;
        } catch (Exception $exception) {
            return \false;
        }
    }
    /**
     * Set the time of a date to the start of the day.
     *
     * @param \DateTimeInterface $date The source date.
     *
     * @return \DateTime
     *
     * @since 1.0.0
     */
    public function start_of_day(DateTimeInterface $date)
    {
        $start_of_day = $this->to_datetime($date);
        $start_of_day->setTime(0, 0, 0);
        return $start_of_day;
    }
    /**
     * Determine whether a date is after another date.
     *
     * @param \DateTimeInterface $date The date to compare.
     * @param \DateTimeInterface $other The reference date.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function is_after(DateTimeInterface $date, DateTimeInterface $other)
    {
        return $date > $other;
    }
    /**
     * Convert a date to a SQL safe datetime string.
     *
     * @param \DateTimeInterface $date The date to format.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function to_sql_datetime_string(DateTimeInterface $date)
    {
        return $date->format(static::BASE_FORMAT);
    }
    /**
     * Resolve a timezone value to a DateTimeZone instance.
     *
     * @param \DateTimeZone|string|int|null $timezone Optional timezone.
     *
     * @return \DateTimeZone
     *
     * @since 1.0.0
     */
    protected function resolve_timezone($timezone = null)
    {
        if ($timezone instanceof DateTimeZone) {
            return $timezone;
        }
        if (\is_string($timezone) && $timezone !== '') {
            return new DateTimeZone($timezone);
        }
        return new DateTimeZone(\date_default_timezone_get());
    }
    /**
     * Convert a DateTimeInterface instance to a mutable DateTime.
     *
     * @param \DateTimeInterface $date The source date.
     *
     * @return \DateTime
     *
     * @since 1.0.0
     */
    protected function to_datetime(DateTimeInterface $date)
    {
        if ($date instanceof DateTime) {
            return clone $date;
        }
        return new DateTime($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
    }
}
