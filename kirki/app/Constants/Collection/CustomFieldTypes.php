<?php

namespace Kirki\App\Constants\Collection;

use Kirki\Framework\Concerns\HasConstants;

class CustomFieldTypes
{
    use HasConstants;

    const TEXT = 'text';
    const RICH_TEXT = 'rich-text';
    const IMAGE = 'image';
    const VIDEO = 'video';
    const EMAIL = 'email';
    const PHONE = 'phone';
    const NUMBER = 'number';
    const DATE = 'date';
    const TIME = 'time';
    const SWITCH = 'switch';
    const OPTION = 'option';
    const URL = 'url';
    const FILE = 'file';
    const GALLERY = 'gallery';
    const REFERENCE = 'reference';
    const MULTI_REFERENCE = 'multi-reference';
}