<?php

namespace Kirki\App\Http\Requests\Page;

use function Kirki\App\ensure_array;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

use function Kirki\App\is_falsy;

class GlobalStyleRequest extends Request
{
    protected function prepare_for_validation()
    {
        $this->merge([
            'styles' => ensure_array($this->styles),
        ]);
    }

    public function rules()
    {
        return [
            'session_id' => 'required|string',
            'styles' => 'nullable|array',
            'styles.*' => 'nullable|array',
            'styles.*.isGlobalStyle' => ['required', fn($value) => is_falsy($value) ? __('The isGlobalStyle field is required.', 'kirki') : true],
        ];
    }

    public function filters()
    {
        return [
            'session_id' => Sanitizer::TEXT,
            'styles' => Sanitizer::ARRAY ,
        ];
    }
}