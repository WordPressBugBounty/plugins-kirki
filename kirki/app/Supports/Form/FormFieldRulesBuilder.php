<?php

namespace Kirki\App\Supports\Form;

defined('ABSPATH') || exit;

use Kirki\App\Constants\Form\FormFieldTypes;
use Kirki\App\Rules\Form\AcceptedFileTypeRule;
use Kirki\App\Rules\Form\MaxFileSizeRule;

/**
 * Turns a Kirki form's `fields` configuration into rules the framework
 * Validator understands.
 *
 * A field's `type` maps to a format rule (email, number, ...) and `required`
 * toggles between the `required`/`nullable` rule — the only two constraints
 * the form builder actually lets users configure. File fields get their own
 * rule set (accepted type + max size) since their value lives in `$_FILES`,
 * not the submitted form data.
 */
class FormFieldRulesBuilder
{
    /**
     * The format rule for each field type that maps directly onto a single
     * Validator rule.
     *
     * @var array<string, string>
     */
    protected const FORMAT_RULES = [
        FormFieldTypes::EMAIL => 'email',
        FormFieldTypes::NUMBER => 'number',
        FormFieldTypes::DATE => 'date',
            // Preserves the legacy (imprecise) datetime-local pattern rather than
            // the stricter `datetime` rule, to avoid rejecting values the old
            // validator accepted.
        FormFieldTypes::DATETIME_LOCAL => 'regex:/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
        FormFieldTypes::TEL => 'regex:/^[0-9]{10}+$/',
    ];

    /**
     * Build the Validator rules for a form's fields.
     *
     * @param array $fields The form's field configuration.
     * @return array<string, array<int, string>>
     */
    public static function rules(array $fields)
    {
        $rules = [];

        foreach ($fields as $name => $field) {
            $rules[$name] = ($field['type'] ?? null) === FormFieldTypes::FILE
                ? static::rules_for_file_field($field)
                : static::rules_for_field($field);
        }

        return $rules;
    }

    /**
     * Build the data to validate: submitted form data with empty values
     * normalized to null, and file fields' values pulled from `$_FILES`.
     *
     * The Validator only skips format rules for a field it never received;
     * an empty string is still "received". Normalizing empty values to null
     * here keeps optional fields from failing format checks while `required`
     * still catches them via its own missing-value check.
     *
     * @param array $form_data The submitted form data.
     * @param array $fields    The form's field configuration.
     * @param array $files     The file array of our own file class.
     * @return array
     */
    public static function data_for_validation(array $form_data, array $fields, array $files = [])
    {
        foreach ($files as $field_name => $file) {
            $field = $fields[$field_name] ?? [];

            if (($field['type'] ?? null) !== FormFieldTypes::FILE) {
                continue;
            }

            if (!$file->is_valid()) {
                $form_data[$field_name] = null;
                continue;
            }

            $form_data[$field_name] = $file;
        }

        foreach ($fields as $name => $field) {
            if (($field['type'] ?? null) !== FormFieldTypes::FILE && isset($form_data[$name]) && empty($form_data[$name])) {
                $form_data[$name] = null;
            }
        }

        return $form_data;
    }

    /**
     * The Validator rules for a single field.
     *
     * @param array $field The field configuration.
     * @return array<int, string>
     */
    protected static function rules_for_field(array $field)
    {
        $rules = [static::required_rule($field)];

        if (isset(static::FORMAT_RULES[$field['type'] ?? null])) {
            $rules[] = static::FORMAT_RULES[$field['type']];
        }

        return $rules;
    }

    /**
     * The Validator rules for a single file field.
     *
     * @param array $field The field configuration.
     * @return array<int, string>
     */
    protected static function rules_for_file_field(array $field)
    {
        return [
            static::required_rule($field),
            AcceptedFileTypeRule::class . ':' . ($field['accept'] ?? 'default'),
            MaxFileSizeRule::class . ':' . ($field['max-file-size'] ?? 2),
        ];
    }

    /**
     * The `required`/`nullable` rule for a field.
     *
     * @param array $field The field configuration.
     * @return string
     */
    protected static function required_rule(array $field)
    {
        return !empty($field['required']) ? 'required' : 'nullable';
    }
}
