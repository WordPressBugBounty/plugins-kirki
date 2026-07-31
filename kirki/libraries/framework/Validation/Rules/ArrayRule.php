<?php

/**
 * Array rule class.
 *
 * @package    Framework
 * @subpackage Validation
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

use Kirki\Framework\Validation\ValidationRule;
\defined('ABSPATH') || exit;
/**
 * Validates that the given value is an array.
 *
 * @method $this min(int $min)
 * @method $this max(int $max)
 * @method $this size(int $size)
 * @method $this exactly(int $exactly)
 * @method $this contains(mixed $contains)
 */
class ArrayRule extends ValidationRule
{
    /**
     * The rule name.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected string $rule = 'array';
    /**
     * The supported constraints.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected array $constraints = ['min', 'max', 'size', 'exactly', 'contains'];
    /**
     * Validate the rule.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate() : bool
    {
        if (!\is_iterable($this->value)) {
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
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_min() : bool
    {
        return \count($this->value) >= (int) $this->get('min');
    }
    /**
     * Validate the max constraint.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_max() : bool
    {
        return \count($this->value) <= (int) $this->get('max');
    }
    /**
     * Validate the size constraint.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_size() : bool
    {
        return $this->has_exact_count('size');
    }
    /**
     * Validate the exactly constraint.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_exactly() : bool
    {
        return $this->has_exact_count('exactly');
    }
    /**
     * Check whether the value item count matches the given constraint value.
     *
     * @param string $constraint The constraint holding the expected count.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function has_exact_count(string $constraint) : bool
    {
        return \count($this->value) === (int) $this->get($constraint);
    }
    /**
     * Validate the contains constraint.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function validate_contains() : bool
    {
        return \in_array($this->get('contains'), $this->value);
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
