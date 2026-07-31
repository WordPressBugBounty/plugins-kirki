<?php

namespace Kirki\App\Http\Requests\Page;

defined('ABSPATH') || exit;

use Exception;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Http\Response;
use Kirki\Framework\Sanitizer;

use function Kirki\App\ensure_array;

class PageDataRequest extends Request
{
    protected function prepare_for_validation()
    {
        $this->merge([
            'data' => ensure_array($this->data),
        ]);
    }

    public function rules()
    {
        $rules = [
            'session_id' => 'required|string',
            'is_staging' => 'nullable|boolean',
            'data' => 'required|array',
            'data.blocks' => 'prohibited',
            'data.styles' => 'prohibited',
            'data.usedStyles' => 'prohibited',
            'data.usedStyleIdsRandom' => 'prohibited',
            'data.usedFonts' => 'prohibited',

        ];

        switch ($this->page_content_type) {
            case 'blocks':
                $rules = array_merge($rules, [
                    'data.blocks' => 'nullable|array',
                ]);
                break;
            case 'styles':
                $rules = array_merge($rules, [
                    'data.styles' => 'nullable|array',
                ]);
                break;
            case 'used-styles':
                $rules = array_merge($rules, [
                    'data.usedStyles' => 'nullable|array',
                ]);
                break;
            case 'used-style-ids-random':
                $rules = array_merge($rules, [
                    'data.usedStyleIdsRandom' => 'nullable|array',
                ]);
                break;
            case 'used-fonts':
                $rules = array_merge($rules, [
                    'data.usedFonts' => 'nullable|array',
                ]);
                break;
            default:
                throw new Exception(__('Unknown request', 'kirki'), Response::NOT_FOUND);
        }

        return $rules;
    }

    public function filters()
    {
        return [
            'data' => Sanitizer::ARRAY ,
            'data.blocks' => Sanitizer::ARRAY ,
            'data.styles' => Sanitizer::ARRAY ,
            'data.usedStyles' => Sanitizer::ARRAY ,
            'data.usedStyleIdsRandom' => Sanitizer::ARRAY ,
            'data.usedFonts' => Sanitizer::ARRAY ,
            'session_id' => Sanitizer::TEXT,
            'is_staging' => Sanitizer::BOOL,
        ];
    }
}