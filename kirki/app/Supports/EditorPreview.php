<?php

namespace Kirki\App\Supports;

defined('ABSPATH') || exit;

use Kirki\App\Constants\GlobalDataKeys;
use Kirki\App\Supports\Facades\GlobalData;

use function Kirki\App\get_request_header;

class EditorPreview
{
    public static function is_valid_request()
    {
        $token = static::get_token_from_header();

        if (!empty($token)) {
            return true;
        }

        return false;
    }

    public static function has_valid_token()
    {
        $submitted_token = static::get_token_from_header();

        if (empty($submitted_token)) {
            return false;
        }

        $status = GlobalData::get(GlobalDataKeys::EDITOR_READ_ONLY_ACCESS_STATUS);

        if (!$status) {
            return false;
        }

        $editor_read_only_access_token = GlobalData::get(GlobalDataKeys::EDITOR_READ_ONLY_ACCESS_TOKEN);

        if ($editor_read_only_access_token && $editor_read_only_access_token === $submitted_token) {
            return true;
        }

        return false;
    }

    public static function get_token_from_header()
    {
        return get_request_header('Editor-Preview-Token');
    }
}