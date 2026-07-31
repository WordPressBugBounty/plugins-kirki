<?php

namespace Kirki\App\Supports;

defined('ABSPATH') || exit;

use Kirki\App\DTO\Form\FormConfigDTO;

class ActionHooks
{
    /**
     * @param array         $form_data
     * @param FormConfigDTO $form_config
     */
    public static function kirki_form_submitted($form_data, FormConfigDTO $form_config)
    {
        do_action('kirki_form_submitted', $form_data, $form_config->to_array());
    }
}
