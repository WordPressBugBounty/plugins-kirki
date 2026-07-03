<?php

namespace Kirki\App\Http\Requests\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class PopupRequest extends Request
{
    public function rules()
    {
        return [
            'blocks' => 'array',
            'styleBlocks' => 'array',
            'usedFonts' => 'array',
        ];
    }

    public function filters()
    {
        return [
            'blocks' => Sanitizer::ARRAY,
            'styleBlocks' => Sanitizer::ARRAY,
            'usedFonts' => Sanitizer::ARRAY,
        ];
    }
}