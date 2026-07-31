<?php

defined('ABSPATH') || exit;

use Kirki\App\FormActions\Actions\EmailActionHandler;
use Kirki\App\FormActions\Actions\WebhookActionHandler;
use Kirki\App\Constants\Form\FormActionTypes;

/**
 * Form submission action handlers, keyed by action type.
 *
 * Each handler owns one delivery channel. To add a new action type, create a
 * handler implementing FormActionHandler and map it here under a key that
 * matches the action `type` value authored on the frontend.
 */
return [
    'handlers' => [
        FormActionTypes::EMAIL => EmailActionHandler::class,
        FormActionTypes::WEBHOOK => WebhookActionHandler::class,
    ],
];
