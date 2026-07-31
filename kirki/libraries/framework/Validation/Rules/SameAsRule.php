<?php

/**
 * Same as rule class.
 *
 * @package    Framework
 * @subpackage Validation
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

use Kirki\Framework\Validation\ValidationRule;
use function Kirki\Framework\deep_get;
\defined('ABSPATH') || exit;
/**
 * Validates that the given value strictly matches the value of another field.
 */
class SameAsRule extends ValidationRule
{
    /**
     * The rule name.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected string $rule = 'same_as';
    /**
     * Validate the rule.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate() : bool
    {
        $other_value = deep_get($this->data, (string) $this->args);
        if ($this->value !== $other_value) {
            return $this->fails($this->default_messages['default']);
        }
        return \true;
    }
    /**
     * Get the error messages.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function messages()
    {
        return $this->process_messages($this->messages);
    }
}
