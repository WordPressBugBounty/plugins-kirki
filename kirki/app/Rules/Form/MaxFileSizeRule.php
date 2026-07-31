<?php

namespace Kirki\App\Rules\Form;

defined('ABSPATH') || exit;

use Kirki\Framework\Validation\Rules\BaseRule;

/**
 * Validates an uploaded file's size against a form field's max size, in MB.
 *
 * Expects the value to be a raw `$_FILES` entry, or null when no file was
 * submitted (handled by the base rule's default null-skip).
 */
class MaxFileSizeRule extends BaseRule
{
    public function validate_rule()
    {
        if (empty($this->value) || !$this->value->is_valid()) {
            return false;
        }

        return ($this->value->get_size() / 1048576) <= (float) $this->rule_value;
    }

    public function get_error_message()
    {
        return sprintf(__('File size exceeds the maximum allowed for this field (%s MB).', 'kirki'), $this->rule_value);
    }
}
