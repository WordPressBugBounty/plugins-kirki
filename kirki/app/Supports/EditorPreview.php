<?php

namespace Kirki\App\Supports;

defined('ABSPATH') || exit;

use Kirki\App\Constants\GlobalDataKeys;
use Kirki\App\Supports\Facades\GlobalData;

use function Kirki\App\get_request_header;
use function Kirki\App\is_falsy;

class EditorPreview
{
    protected static function has_token()
    {
        return !empty(static::get_token_from_header());
    }

    public static function has_valid_token()
    {
        if (!static::has_token()) {
            return false;
        }

        $status = GlobalData::get(GlobalDataKeys::EDITOR_READ_ONLY_ACCESS_STATUS);

        if (is_falsy($status)) {
            return false;
        }

        $editor_read_only_access_token = GlobalData::get(GlobalDataKeys::EDITOR_READ_ONLY_ACCESS_TOKEN);

        return $editor_read_only_access_token && $editor_read_only_access_token === static::get_token_from_header();
    }

    public static function get_token_from_header()
    {
        return get_request_header('Editor-Preview-Token');
    }
}