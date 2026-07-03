<?php

namespace Kirki\App\Http\Requests\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class TogglePageSymbolRequest extends Request
{
    public function rules()
    {
        return [
            'post_id' => 'required|integer',
            'symbol_type' => 'required|string',
            'disable' => 'required|boolean',
        ];
    }

    public function filters()
    {
        return [
            'post_id' => Sanitizer::INT,
            'symbol_type' => Sanitizer::TEXT,
            'disable' => Sanitizer::BOOL,
        ];
    }
}