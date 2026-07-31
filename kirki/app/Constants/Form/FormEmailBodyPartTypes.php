<?php

namespace Kirki\App\Constants\Form;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

/**
 * Supported part types for a composed email action body.
 *
 * These values mirror the `type` key stored on each entry of an email
 * action's `body` configuration.
 */
class FormEmailBodyPartTypes
{
    use HasConstants;

    const TEXT = 'text';
    const FORM = 'form';
}
