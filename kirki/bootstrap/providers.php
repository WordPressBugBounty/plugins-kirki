<?php

defined( 'ABSPATH' ) || exit;

use Kirki\App\Providers\FacadeProvider;
use Kirki\App\Providers\FormServiceProvider;
use Kirki\App\Providers\UserServiceProvider;


return [
    FacadeProvider::class,
    FormServiceProvider::class,
    UserServiceProvider::class,
];

