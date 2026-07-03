<?php

namespace Kirki\App\Http\Requests\Page;

defined('ABSPATH') || exit;

use Kirki\App\Constants\PostTypes;
use Kirki\App\Constants\UtilityPageType;
use Kirki\App\Services\UtilityPageService;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class PageRequest extends Request
{
    public function rules()
    {
        return [
            'post_title' => 'required|string',
            'post_type' => 'required|string',
            'blocks' => 'nullable|array',
            'conditions' => 'nullable|array',
            'collection_type' => 'nullable|string',
            'utility_page_type' => [
                'nullable',
                'string',
                'in:' . UtilityPageType::join(),
                function ($value) {
                    if ($this->post_type === PostTypes::UTILITY) {
                        if (empty($value)) {
                            return __("Utility page type is required.", 'kirki');
                        }

                        $exists = UtilityPageService::create()->exists($value);

                        if ($exists) {
                            /** translators: %s: Utility page type */
                            return sprintf(__("Utility page of type %s already exists."), $value);
                        }
                    }

                    return true;
                }
            ],
            'custom_template' => 'nullable|array',
            'custom_template.url' => 'nullable|url',
        ];
    }

    public function filters()
    {
        return [
            'post_title' => Sanitizer::TEXT,
            'post_type' => Sanitizer::TEXT,
            'blocks' => Sanitizer::ARRAY,
            'conditions' => Sanitizer::ARRAY,
            'collection_type' => Sanitizer::TEXT,
            'utility_page_type' => Sanitizer::TEXT,
            'custom_template' => Sanitizer::ARRAY,
            'custom_template.url' => Sanitizer::URL,
        ];
    }
}