<?php

/**
 * Rule to ensure the value is after the given date.
 *
 * @package    Framework
 * @subpackage Validation\Rules
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

\defined('ABSPATH') || exit;
use Kirki\Framework\Supports\Date;
use Kirki\Framework\Supports\Str;
use function Kirki\Framework\message;
class GreaterThanEqualRule extends BaseRule
{
    /**
     * Check if the rule is valid.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate_rule()
    {
        $current_value = $this->value;
        $target_value = $this->rule_value;
        if (!\is_numeric($target_value) && \array_key_exists($target_value, $this->data)) {
            $target_value = $this->data[$target_value];
        }
        $target_value = Str::to_number($target_value);
        if (\is_int($target_value)) {
            $current_value = (int) $current_value;
        }
        if (\is_float($target_value)) {
            $current_value = (float) $current_value;
        }
        return $current_value >= $target_value;
    }
    /**
     * Get the error message if the rule is not valid.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_error_message()
    {
        return message('validator.gte', $this->last_key_segment(), $this->rule_value);
    }
}
