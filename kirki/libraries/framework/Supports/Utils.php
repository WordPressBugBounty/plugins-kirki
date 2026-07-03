<?php

/**
 * Miscellaneous utility methods including UUID generation, growth percentage calculation, and float-to-int rounding.
 * Wraps wp_generate_uuid4 and provides safe numeric helpers for reporting features.
 * Stateless static helpers with no container dependencies.
 *
 * @package    Framework
 * @subpackage Supports
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports;

\defined('ABSPATH') || exit;
class Utils
{
    /**
     * Calculates the percentage growth from the previous to the current value.
     *
     * @param mixed $current The current value.
     * @param mixed $previous The previous value.
     * @param int $precision The number of decimal places to round to. Defaults to 2.
     *
     * @return float The percentage growth.
     *
     * @since 1.0.0
     */
    public static function calculate_growth_in_percent($current, $previous, $precision = 2)
    {
        if (!\is_numeric($current) || !\is_numeric($previous)) {
            return 0.0;
        }
        $current = \ctype_digit((string) $current) ? (int) $current : (float) $current;
        $previous = \ctype_digit((string) $previous) ? (int) $previous : (float) $previous;
        if ($previous === 0 || $previous === 0.0) {
            return $current > 0 ? 100 : 0;
        }
        return \round(($current - $previous) / $previous * 100, $precision);
    }
    /**
     * Generates a UUID.
     *
     * @return string The UUID.
     *
     * @since 1.0.0
     */
    public static function uuid()
    {
        return wp_generate_uuid4();
    }
    /**
     * Converts a float to an integer by rounding up.
     *
     * @param float $value The float value to convert.
     *
     * @return int The rounded integer value.
     *
     * @since 1.0.0
     */
    public static function to_int($value)
    {
        return (int) \round($value, 0, \PHP_ROUND_HALF_UP);
    }
}
