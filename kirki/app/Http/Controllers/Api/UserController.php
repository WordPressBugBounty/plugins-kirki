<?php

namespace Kirki\App\Http\Controllers\Api;

defined('ABSPATH') || exit;

use Kirki\App\Constants\UserMetaKeys;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Wordpress\UserMeta;

use function Kirki\App\to_boolean;
use function Kirki\Framework\response;
use function Kirki\Framework\user;

class UserController
{
    public function is_logged_in(Request $request)
    {
        $is_logged_in = user()->is_logged_in();

        return response()->json([
            'data' => [
                'is_logged_in' => $is_logged_in,
            ],
        ]);
    }

    public function set_walkthrough_state(Request $request) {
        $state = $request->bool('walkthrough_shown_state', false);

        UserMeta::update(user()->get_id(), UserMetaKeys::WALKTHROUGH_SHOWN_STATE, $state);

        return response()->json([
            'data' => true,
        ]);
    }

    public function get_walkthrough_state(Request $request) {
        $state = UserMeta::get(user()->get_id(), UserMetaKeys::WALKTHROUGH_SHOWN_STATE);

        return response()->json([
            'data' => to_boolean($state),
        ]);
    }
}