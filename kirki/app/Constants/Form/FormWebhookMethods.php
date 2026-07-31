<?php

namespace Kirki\App\Constants\Form;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

/**
 * Supported HTTP methods for a webhook action.
 *
 * These values mirror the `method` key stored on a form's `webhooks` action
 * configuration.
 */
class FormWebhookMethods
{
    use HasConstants;

    const GET = 'get';
    const POST = 'post';
}
