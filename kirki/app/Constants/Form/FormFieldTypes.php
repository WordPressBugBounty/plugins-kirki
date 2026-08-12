<?php

namespace Kirki\App\Constants\Form;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

/**
 * Supported input types for a submittable form field.
 *
 * These values mirror the `type` key stored on each entry of a form's
 * `fields` configuration.
 */
class FormFieldTypes
{
    use HasConstants;

    const EMAIL = 'email';
    const NUMBER = 'number';
    const TEL = 'tel';
    const DATE = 'date';
    const DATETIME_LOCAL = 'datetime-local';

    /**
     * @deprecated File upload is no longer supported.
     */
    const FILE = 'file';
}
