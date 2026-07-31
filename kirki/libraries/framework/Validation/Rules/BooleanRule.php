<?php

/**
 * Boolean rule class.
 *
 * @package    Framework
 * @subpackage Validation
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

use Kirki\Framework\Validation\ValidationRule;
\defined('ABSPATH') || exit;
/**
 * Validates that the given value is a boolean.
 *
 * Accepts true/false, 0/1, '0'/'1', 'true'/'false' by default.
 * When the strict constraint is applied, only real booleans pass.
 */
class BooleanRule extends ValidationRule
{
    /**
     * The rule name.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected string $rule = 'boolean';
    /**
     * The supported constraints.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected array $constraints = ['strict'];
    /**
     * The accepted boolean representations.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected array $accepted_values = [\true, \false, 0, 1, '0', '1', 'true', 'false'];
    /**
     * Create a new boolean rule instance.
     *
     * @param mixed $args The rule arguments.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct($args = null)
    {
        parent::__construct($args);
        if ($args === 'strict') {
            $this->set('strict', \true);
        }
    }
    /**
     * Validate the rule.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate() : bool
    {
        if ($this->get('strict') === \true) {
            return \is_bool($this->value) ? \true : $this->fails($this->default_messages['default']);
        }
        if (!\in_array($this->value, $this->accepted_values, \true)) {
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
