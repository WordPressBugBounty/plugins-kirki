<?php

defined( 'ABSPATH' ) || exit;

use Kirki\App\Providers\FacadeProvider;
use Kirki\App\Providers\UserServiceProvider;


return [
    FacadeProvider::class,
    UserServiceProvider::class,
];

