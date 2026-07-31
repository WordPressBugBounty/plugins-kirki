<?php

namespace Kirki\App\Contracts;

use Exception;

defined('ABSPATH') || exit;

use Kirki\App\DTO\Form\FormConfigDTO;

/**
 * A single form-submission action channel (email, webhook, mail client, ...).
 *
 * Each handler runs one configured action of its type. The dispatcher routes
 * actions by type, so handlers stay fully independent — a new or changed
 * handler can never break another.
 */
interface FormActionHandler
{
    /**
     * Run a single configured action for the given submission.
     *
     * @param array         $action      A single configured action of this handler's type.
     * @param array         $form_data   The submission data.
     * @param FormConfigDTO $form_config The form configuration.
     * @return bool Whether it succeeded. Fire-and-forget channels return true.
     * @throws Exception on error
     */
    public function handle(array $action, array $form_data, FormConfigDTO $form_config);
}
