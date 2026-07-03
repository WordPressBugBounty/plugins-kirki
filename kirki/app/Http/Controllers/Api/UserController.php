<?php

namespace Kirki\App\Http\Controllers\Api;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;

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
}