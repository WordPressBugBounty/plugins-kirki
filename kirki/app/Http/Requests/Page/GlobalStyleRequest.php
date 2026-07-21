<?php

namespace Kirki\App\Http\Requests\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

use function Kirki\App\is_falsy;

class GlobalStyleRequest extends Request
{
    public function rules()
    {
        return [
            'session_id' => 'required|string',
            'styles' => 'array',
            'styles.*' => 'array',
            'styles.*.isGlobalStyle' => ['required', fn ($value) => is_falsy($value) ? __('The isGlobalStyle field is required.', 'kirki') : true],
        ];
    }

    public function filters()
    {
        return [
            'session_id' => Sanitizer::TEXT,
            'styles' => Sanitizer::ARRAY,
        ];
    }
}