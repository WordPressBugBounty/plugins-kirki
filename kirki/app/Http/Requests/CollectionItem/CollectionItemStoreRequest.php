<?php

namespace Kirki\App\Http\Requests\CollectionItem;

use Kirki\App\Constants\PostStatus;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;
use Kirki\Framework\Supports\Arr;

use function Kirki\App\ensure_array;

class CollectionItemStoreRequest extends Request
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

        if (empty($data['post_status'])) {
            $this->merge(['post_status' => PostStatus::DRAFT]);
        }

        $this->merge([
            'fields' => ensure_array($this->fields),
        ]);
    }

    /**
     * Validation rules.
     */
    public function rules()
    {
        return [
            'ID' => 'nullable|integer',
            'post_parent' => 'required|integer',
            'post_title' => 'required|string',
            'post_name' => 'nullable|string',
            'fields' => 'nullable|array',
            'post_status' => 'nullable|string|in:' . Arr::join([
                PostStatus::DRAFT,
                PostStatus::PUBLISH,
                PostStatus::FUTURE,
            ]),
            'post_date' => 'nullable',
        ];
    }

    /**
     * Sanitization filters.
     */
    public function filters()
    {
        return [
            'ID' => Sanitizer::INT,
            'post_parent' => Sanitizer::INT,
            'post_title' => Sanitizer::TEXT,
            'post_name' => Sanitizer::TEXT,
            'fields' => Sanitizer::ARRAY ,
            'post_status' => Sanitizer::TEXT,
            'post_date' => Sanitizer::DATETIME,
        ];
    }
}
