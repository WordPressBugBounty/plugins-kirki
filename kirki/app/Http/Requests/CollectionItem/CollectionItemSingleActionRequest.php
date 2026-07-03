<?php

namespace Kirki\App\Http\Requests\CollectionItem;

use Kirki\App\Constants\Collection\ActionTypes;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Sanitizer;

class CollectionItemSingleActionRequest extends Request
{
    /**
     * Validation rules.
     */
    public function rules()
    {
        return [
            'post_id' => 'required|integer',
            'action' => 'required|in:' . ActionTypes::join(), // @todo: improve later
        ];
    }

    /**
     * Sanitization filters.
     */
    public function filters()
    {
        return [
            'post_id' => Sanitizer::INT,
            'action' => Sanitizer::TEXT,
        ];
    }
}
