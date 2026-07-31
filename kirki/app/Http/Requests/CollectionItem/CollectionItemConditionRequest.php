<?php

namespace Kirki\App\Http\Requests\CollectionItem;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

use function Kirki\App\ensure_array;

class CollectionItemConditionRequest extends Request
{
    protected function prepare_for_validation()
    {
        $this->merge([
            'conditions' => ensure_array($this->conditions),
        ]);
    }

    public function rules()
    {
        return [
            'query' => 'nullable|string',
            'conditions' => 'nullable|array',
            'conditions.*.type' => 'nullable|string',
            'conditions.*.post_type' => 'nullable|string',
            'conditions.*.from' => 'nullable|string',
            'conditions.*.to' => 'nullable|string',
            'conditions.*.category' => 'nullable|string',
            'conditions.*.where' => 'nullable|string',
        ];
    }

    public  function filters()
    {
        return [
            'query' => Sanitizer::TEXT,
            'conditions' => Sanitizer::ARRAY,
            'conditions.*.type' => Sanitizer::TEXT,
            'conditions.*.post_type' => Sanitizer::TEXT,
            'conditions.*.from' => Sanitizer::TEXT,
            'conditions.*.to' => Sanitizer::TEXT,
            'conditions.*.category' => Sanitizer::TEXT,
            'conditions.*.where' => Sanitizer::TEXT,
        ];
    }
}