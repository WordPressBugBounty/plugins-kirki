<?php

namespace Kirki\App\Http\Requests\Collection;

use Kirki\App\Constants\Collection\CustomFieldTypes;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

use function Kirki\App\ensure_array;

class CollectionStoreRequest extends Request
{
    /**
     * Prepare data for validation.
     */
    protected function prepare_for_validation()
    {
        $data = ensure_array($this->get('data') ?? []);

        $this->merge($data);

        $this->merge([
            'fields' => ensure_array($this->fields),
            'basic_fields' => ensure_array($this->basic_fields),
        ]);
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
            'preset_type' => 'nullable|string',

            'fields' => 'nullable|array',
            'fields.*.id' => 'required|string',
            'fields.*.kind' => 'required|string|in:custom',
            'fields.*.type' => 'required|string|in:' . CustomFieldTypes::join(),
            'fields.*.title' => 'required|string',
            'fields.*.help_text' => 'nullable|string',
            'fields.*.required' => 'required|boolean',
            'fields.*.templateKey' => 'nullable|string',

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
            'preset_type' => Sanitizer::TEXT,

            'fields' => Sanitizer::ARRAY ,
            'fields.*.id' => Sanitizer::TEXT,
            'fields.*.kind' => Sanitizer::TEXT,
            'fields.*.type' => Sanitizer::TEXT,
            'fields.*.title' => Sanitizer::TEXT,
            'fields.*.help_text' => Sanitizer::TEXT,
            'fields.*.required' => Sanitizer::BOOL,
            'fields.*.templateKey' => Sanitizer::TEXT,

            'basic_fields' => Sanitizer::ARRAY ,
        ];
    }
}
