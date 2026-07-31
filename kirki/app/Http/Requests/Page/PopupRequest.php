<?php

namespace Kirki\App\Http\Requests\Page;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

use function Kirki\App\ensure_array;

class PopupRequest extends Request
{
    protected function prepare_for_validation()
    {
        $this->merge([
            'blocks' => ensure_array($this->blocks),
            'styleBlocks' => ensure_array($this->styleBlocks),
            'usedFonts' => ensure_array($this->usedFonts),
        ]);
    }

    public function rules()
    {
        return [
            'blocks' => 'nullable|array',
            'styleBlocks' => 'nullable|array',
            'usedFonts' => 'nullable|array',
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