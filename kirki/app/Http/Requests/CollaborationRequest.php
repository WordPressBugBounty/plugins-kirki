<?php

namespace Kirki\App\Http\Requests;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

use function Kirki\App\ensure_array;

class CollaborationRequest extends Request
{
    protected function prepare_for_validation()
    {
        $this->merge([
            'data' => ensure_array($this->data),
        ]);
    }

    public function rules()
    {
        return [
            'data' => 'required|array',
            'data.*.parent' => 'nullable|string',
            'data.*.parent_id' => 'nullable|integer',
            'data.*.action' => 'nullable|array',
            'session_id' => 'required|string',
        ];
    }

    public function filters()
    {
        return [
            'data' => Sanitizer::ARRAY,
            'data.*.parent' => Sanitizer::TEXT,
            'data.*.parent_id' => Sanitizer::INT,
            'data.*.action' => Sanitizer::ARRAY,
            'session_id' => Sanitizer::TEXT,
        ];
    }
}