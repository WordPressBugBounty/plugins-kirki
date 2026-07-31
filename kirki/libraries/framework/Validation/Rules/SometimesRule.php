<?php

/**
 * Nullable rule class.
 *
 * @package    Framework
 * @subpackage Validation
 * @since      1.0.0
 */
namespace Kirki\Framework\Validation\Rules;

use Kirki\Framework\Validation\ValidationRule;
\defined('ABSPATH') || exit;
class SometimesRule extends ValidationRule
{
    /**
     * The rule name.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected string $rule = 'sometimes';
    /**
     * Validate the rule.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function validate() : bool
    {
        return \true;
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
        return [];
    }
}
