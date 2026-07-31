<?php

/**
 * Numeric rule class.
 *
 * @package    Framework
 * @subpackage Validation
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

use Kirki\Framework\Validation\ValidationRule;
\defined('ABSPATH') || exit;
/**
 * Validates that the given value is numeric.
 *
 * @method $this integer()
 * @method $this int()
 * @method $this min(int $min)
 * @method $this max(int $max)
 */
class NumericRule extends ValidationRule
{
    /**
     * The rule name.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected string $rule = 'numeric';
    /**
     * The supported constraints.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected array $constraints = ['min', 'max', 'integer', 'int', 'float', 'decimal', 'gt', 'gte', 'lt', 'lte'];
    /**
     * Validate the rule.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate() : bool
    {
        if (!\is_numeric($this->value)) {
            return $this->fails($this->default_messages['default']);
        }
        return $this->validate_constraints(function ($passed, $constraint) {
            if (!$passed) {
                $this->fails($this->default_messages[$constraint], [$constraint => $this->get($constraint)]);
            }
        });
    }
    /**
     * Validate the min constraint.
     *
     * @param string $value The value to validate.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_min($value)
    {
        return \floatval($value) >= \floatval($this->get('min'));
    }
    /**
     * Validate the max constraint.
     *
     * @param string $value The value to validate.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_max($value)
    {
        return \floatval($value) <= \floatval($this->get('max'));
    }
    /**
     * Validate the integer constraint.
     *
     * @param string $value The value to validate.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_integer($value)
    {
        return \filter_var($value, \FILTER_VALIDATE_INT) !== \false;
    }
    /**
     * Validate the float constraint.
     *
     * @param string $value The value to validate.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_float($value)
    {
        return \filter_var($value, \FILTER_VALIDATE_FLOAT) !== \false;
    }
    /**
     * Validate the decimal constraint.
     *
     * @param string $value The value to validate.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_decimal($value)
    {
        return \filter_var($value, \FILTER_VALIDATE_FLOAT) !== \false;
    }
    /**
     * Validate the int constraint.
     *
     * @param string $value The value to validate.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_int($value)
    {
        return $this->validate_integer($value);
    }
    /**
     * Validate the gt constraint.
     *
     * @param string $value The value to validate.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_gt($value)
    {
        return \floatval($value) > \floatval($this->get('gt'));
    }
    /**
     * Validate the gte constraint.
     *
     * @param string $value The value to validate.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_gte($value)
    {
        return \floatval($value) >= \floatval($this->get('gte'));
    }
    /**
     * Validate the lt constraint.
     *
     * @param string $value The value to validate.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_lt($value)
    {
        return \floatval($value) < \floatval($this->get('lt'));
    }
    /**
     * Validate the lte constraint.
     *
     * @param string $value The value to validate.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_lte($value)
    {
        return \floatval($value) <= \floatval($this->get('lte'));
    }
    /**
     * Get the error message.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function messages()
    {
        return $this->process_messages($this->messages);
    }
}
