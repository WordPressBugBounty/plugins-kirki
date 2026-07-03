<?php

namespace Kirki\App\Constants;

defined('ABSPATH') || exit;

use Kirki\Framework\Concerns\HasConstants;

class UtilityPageType
{
    use HasConstants;

    const NOT_FOUND = '404';
    const LOGIN = 'login';
    const SIGN_UP = 'sign_up';
    const FORGOT_PASSWORD = 'forgot_password';
    const RESET_PASSWORD = 'reset_password';
    const RETRIEVE_USERNAME = 'retrive_username';
}