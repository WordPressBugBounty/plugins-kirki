<?php

namespace Kirki\App\FormActions\Actions;

defined('ABSPATH') || exit;

use Kirki\App\Constants\Form\FormEmailBodyPartTypes;
use Kirki\App\Contracts\FormActionHandler;
use Kirki\App\DTO\Form\FormConfigDTO;

/**
 * Sends a form submission over email, for a single configured `email` action.
 */
class EmailActionHandler implements FormActionHandler
{
    public function handle(array $action, array $form_data, FormConfigDTO $form_config)
    {
        $this->register_shortcodes($action, $form_data); //@todo: maybe we can implement this without wp shortcodes but a different approach as current implementation might collide with other shortcodes registered

        return $this->send_single_email_action($action, $form_data, $form_config->name);
    }

    /**
     * Register shortcodes referenced by the email action's fields.
     *
     * @param array $action
     * @param array $form_data
     * @return void
     */
    protected function register_shortcodes($action, $form_data)
    {
        $this->register_shortcodes_from_field($action['emailList'] ?? '', $form_data);
        $this->register_shortcodes_from_field($action['replyTo'] ?? '', $form_data);
        $this->register_shortcodes_from_field($action['name'] ?? '', $form_data);
        $this->register_shortcodes_from_field($action['subject'] ?? '', $form_data);

        add_shortcode(
            'admin_email',
            function () {
                return get_option('admin_email');
            }
        );
    }

    /**
     * Register shortcodes referenced in a single email action field.
     *
     * @param string $field_value The field value containing shortcodes.
     * @param array  $form_data   The form data to use for shortcode values.
     * @return void
     */
    protected function register_shortcodes_from_field($field_value, $form_data)
    {
        if (empty($field_value)) {
            return;
        }

        $regex = '/\[([^\]]+)\]/';
        preg_match_all($regex, $field_value, $matches);

        foreach (($matches[1] ?? []) as $match) {
            if (isset($form_data[$match])) {
                add_shortcode($match, fn() => $form_data[$match]);
            }
        }
    }

    /**
     * Send a single email action.
     *
     * @param array  $email_action Email action configuration.
     * @param array  $form_data    Form data.
     * @param string $form_name    Form name.
     * @return bool
     */
    protected function send_single_email_action($email_action, $form_data, $form_name)
    {
        $body = $this->convert_form_data_into_html_for_email($form_data);
        $reply_to = '';
        $name = '';
        $subject = 'New ' . $form_name;
        $header = [];

        if (isset($email_action['body']) && is_array($email_action['body'])) {
            $body = $this->build_email_body($email_action['body'], $form_data);
        }

        if (isset($email_action['replyTo'])) {
            $reply_to = do_shortcode($email_action['replyTo']);
        }

        if (isset($email_action['name'])) {
            $name = do_shortcode($email_action['name']);
        }

        if (isset($email_action['subject'])) {
            $subject = do_shortcode($email_action['subject']);
        }

        if (strlen($reply_to) > 0 && strlen($name) > 0) {
            $header = ['Reply-To: ' . $name . ' <' . $reply_to . '>'];
        }

        if (isset($email_action['emailList']) && !empty($email_action['emailList'])) {
            return $this->send_email_notification(do_shortcode($email_action['emailList']), $subject, $body, $header);
        }

        return false;
    }

    /**
     * Build the email body from a body configuration.
     *
     * @param array $body_config Body configuration.
     * @param array $form_data   Form data.
     * @return string Email body HTML.
     */
    protected function build_email_body($body_config, $form_data)
    {
        $body_parts = [];

        foreach ($body_config as $body_data) {
            if (!isset($body_data['type'], $body_data['value'])) {
                continue;
            }

            if ($body_data['type'] === FormEmailBodyPartTypes::TEXT) {
                $body_parts[] = $body_data['value'];
            } elseif ($body_data['type'] === FormEmailBodyPartTypes::FORM && isset($form_data[$body_data['value']])) {
                $body_parts[] = $form_data[$body_data['value']];
            }
        }
        return nl2br(implode('', $body_parts));
    }

    /**
     * Convert form data into an HTML list for email.
     *
     * @param array $form_data Form data.
     * @return string
     */
    protected function convert_form_data_into_html_for_email($form_data = [])
    {
        $html = '<ul>';

        if (is_array($form_data)) {
            foreach ($form_data as $key => $value) {
                $html .= '<li>' . esc_html($key) . ': ' . esc_html($value) . '</li>';
            }
        }

        $html .= '</ul>';
        return $html;
    }

    /**
     * Send an email notification.
     *
     * @param string|string[] $to      Address(es) to send to.
     * @param string          $subject Email subject.
     * @param string          $message Message contents.
     * @param array           $headers Email headers.
     * @return bool
     */
    protected function send_email_notification($to, $subject, $message, $headers = [])
    {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        return wp_mail($to, $subject, $message, $headers);
    }
}
