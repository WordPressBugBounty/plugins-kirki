<?php

namespace Kirki\App\FormActions\Actions;

defined('ABSPATH') || exit;

use Kirki\App\Constants\Form\FormWebhookMethods;
use Kirki\App\Contracts\FormActionHandler;
use Kirki\App\DTO\Form\FormConfigDTO;
use Kirki\Framework\Supports\Facades\Http;

/**
 * Delivers a submission to a single configured webhook endpoint.
 *
 * Unlike the other channels, a webhook transport failure marks the whole
 * submission as failed, so this is the only handler whose return value can be
 * false.
 */
class WebhookActionHandler implements FormActionHandler
{
    public function handle(array $webhook, array $form_data, FormConfigDTO $form_config)
    {
        if (!isset($webhook['action'], $webhook['method'])) {
            return false;
        }

        if ($webhook['method'] === FormWebhookMethods::GET) {
            return $this->send_get($webhook['action'], $form_data);
        }

        if ($webhook['method'] === FormWebhookMethods::POST) {
            return $this->send_post($webhook['action'], $form_data);
        }

        return false;
    }

    /**
     * Send a GET webhook request.
     *
     * @param string $url       Webhook URL.
     * @param array  $form_data Form data.
     * @return bool Success status.
     */
    protected function send_get($url, $form_data)
    {
        $query_string = http_build_query($form_data);
        $url = rtrim($url, '/');
        $url .= '/';

        $response = Http::get($url . '?' . $query_string);

        return $response->status() !== 0;
    }

    /**
     * Send a POST webhook request.
     *
     * @param string $url       Webhook URL.
     * @param array  $form_data Form data.
     * @return bool Success status.
     */
    protected function send_post($url, $form_data)
    {
        $response = Http::as_form()->post($url, $form_data);

        return $response->status() !== 0;
    }
}
