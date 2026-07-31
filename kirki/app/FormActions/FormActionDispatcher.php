<?php

namespace Kirki\App\FormActions;

defined('ABSPATH') || exit;

use Kirki\App\DTO\Form\FormConfigDTO;

use function Kirki\Framework\app;

/**
 * Routes each configured form action to the handler registered for its type.
 *
 * Only the types a submission actually configures are resolved, so a form that
 * uses one action type never instantiates the others' handlers.
 */
class FormActionDispatcher
{
    /**
     * @var array<string, class-string> Action type => handler class.
     */
    protected $handler_map;

    /**
     * @param array<string, class-string> $handler_map Action type => handler class.
     */
    public function __construct(array $handler_map)
    {
        $this->handler_map = $handler_map;
    }

    /**
     * Dispatch every configured action to its handler.
     *
     * @param array         $form_data   The submission data.
     * @param FormConfigDTO $form_config The form configuration.
     * @return bool Whether every dispatched action succeeded.
     * @throws Exception on error
     */
    public function dispatch(array $form_data, FormConfigDTO $form_config)
    {
        $success = true;
        $handlers = [];

        foreach ($form_config->actions as $action) {
            $type = $action['type'] ?? null;

            if (!$type || !isset($this->handler_map[$type])) {
                continue;
            }

            $handler = $handlers[$type] ?? ($handlers[$type] = app($this->handler_map[$type]));
            $success = $handler->handle($action, $form_data, $form_config) && $success;
        }

        return $success;
    }
}
