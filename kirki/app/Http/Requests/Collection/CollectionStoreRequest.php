<?php

namespace Kirki\App\Http\Requests\Collection;

use Kirki\App\Constants\Collection\CustomFieldTypes;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class CollectionStoreRequest extends Request
{
    /**
     * Prepare data for validation.
     */
    protected function prepare_for_validation()
    {
        $data = $this->get('data');

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (is_array($data)) {
            $this->merge($data);
        }
    }

    /**
     * Validation rules.
     */
    public function rules()
    {
        return [
            'ID' => 'nullable',
            'post_title' => 'required|string',
            'post_name' => 'nullable|string',

            'fields' => 'nullable|array',
            'fields.*.id' => 'required|string',
            'fields.*.kind' => 'required|string|in:custom',
            'fields.*.type' => 'required|string|in:' . CustomFieldTypes::join(),
            'fields.*.title' => 'required|string',
            'fields.*.help_text' => 'nullable|string',
            'fields.*.required' => 'required|boolean',

            'basic_fields' => 'nullable|array',
        ];
    }

    /**
     * Sanitization filters.
     */
    public function filters()
    {
        return [
            'ID' => Sanitizer::INT,
            'post_title' => Sanitizer::TEXT,
            'post_name' => Sanitizer::TEXT,

            'fields' => Sanitizer::ARRAY ,
            'fields.*.id' => Sanitizer::TEXT,
            'fields.*.kind' => Sanitizer::TEXT,
            'fields.*.type' => Sanitizer::TEXT,
            'fields.*.title' => Sanitizer::TEXT,
            'fields.*.help_text' => Sanitizer::TEXT,
            'fields.*.required' => Sanitizer::BOOL,

            'basic_fields' => Sanitizer::ARRAY ,
        ];
    }
}
