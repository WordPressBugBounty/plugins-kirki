<?php

namespace Kirki\App\Constants\Form;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

/**
 * Supported form-submission action types.
 *
 * These values mirror the `type` key stored on each entry of a form's
 * `actions` configuration.
 */
class FormActionTypes
{
    use HasConstants;

    const EMAIL = 'email';
    const WEBHOOK = 'webhooks';
}
