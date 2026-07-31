<?php

namespace Kirki\App\Rules\Form;

defined('ABSPATH') || exit;

use Kirki\Framework\Validation\Rules\BaseRule;

use function Kirki\Framework\config;

/**
 * Validates an uploaded file's MIME type against a form field's `accept`
 * preset (looked up in config('form-uploads.accepted_mime_types')).
 *
 * Expects the value to be a raw `$_FILES` entry, or null when no file was
 * submitted (handled by the base rule's default null-skip).
 */
class AcceptedFileTypeRule extends BaseRule
{
    public function validate_rule()
    {
        if (empty($this->value) || !$this->value->is_valid()) {
            return false;
        }

        $accept = $this->rule_value ? $this->rule_value : 'default';
        $accepted_mime_types = config('form-uploads.accepted_mime_types', []);
        $allowed = $accepted_mime_types[$accept] ?? $accepted_mime_types['default'] ?? [];

        return in_array($this->value->get_client_mime_type(), $allowed, true);
    }

    public function get_error_message()
    {
        return sprintf(__('File type "%s" is not allowed for this field.', 'kirki'), $this->value['type'] ?? '');
    }
}
