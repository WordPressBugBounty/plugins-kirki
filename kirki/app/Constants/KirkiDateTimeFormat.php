<?php

namespace Kirki\App\Constants;

defined('ABSPATH') || exit;

use Kirki\Framework\Constants\DateTimeFormats;

class KirkiDateTimeFormat extends DateTimeFormats
{
    const HUMAN_READABLE_DAY_OF_MONTH_WITH_TIME = 'F j g:i A';
}