<?php

namespace Kirki\App\Http\Requests;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

use function Kirki\Framework\is_valid_json;

class DataRequest extends Request
{
    public function rules()
    {
        return [
            'data' => [
                'required',
                function ($value) {
                    if (is_array($value)) {
                        return true;
                    }

                    if (is_valid_json($value)) {
                        return true;
                    }

                    return __("The data must be an array or a valid JSON string.", 'kirki');
                }
            ],
        ];
    }

    public function filters()
    {
        return [
            'data' => Sanitizer::ARRAY
        ];
    }
}