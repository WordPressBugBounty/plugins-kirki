<?php

namespace Kirki\App\Http\Controllers\Api;

defined('ABSPATH') || exit;

use Kirki\App\Broadcasting\BroadcastManager;
use Kirki\App\Broadcasting\PusherBroadcaster;
use Kirki\Framework\Http\Request;

use function Kirki\Framework\response;
use function Kirki\Framework\user;

class BroadcastingController
{
    /**
     * Sign the subscription of a private or presence collaboration channel.
     */
    public function authenticate(Request $request)
    {
        $broadcaster = (new BroadcastManager())->driver();

        if (!$broadcaster instanceof PusherBroadcaster) {
            return response()->json(['message' => 'Websocket broadcasting is disabled.'], 400);
        }

        $member = [
            'user_id'   => $request->string('session_id'),
            'user_info' => [
                'user_id'   => user()->get_id(),
                'user_avatar' => user()->get_avatar(),
                'user_name' => user()->get_display_name(),
            ],
        ];

        return response()->json(
            $broadcaster->authenticate(
                $request->string('socket_id'),
                $request->string('channel_name'),
                $member
            )
        );
    }
}
