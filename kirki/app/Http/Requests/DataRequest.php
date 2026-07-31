<?php

namespace Kirki\App\Http\Requests;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

use function Kirki\App\ensure_array;
use function Kirki\Framework\is_valid_json;

class DataRequest extends Request
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
            'data' => 'required|array'
        ];
    }

    public function filters()
    {
        return [
            'data' => Sanitizer::ARRAY
        ];
    }
}