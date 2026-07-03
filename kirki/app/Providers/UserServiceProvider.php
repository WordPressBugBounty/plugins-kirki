<?php

namespace Kirki\App\Providers;

use Kirki\App\Wordpress\User;
use Kirki\Framework\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Register the hooks to the application.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\Kirki\Framework\Wordpress\User::class, function () {
            return new User();
        });
    }
}
