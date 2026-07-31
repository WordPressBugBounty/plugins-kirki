<?php

namespace Kirki\App\Providers;

defined('ABSPATH') || exit;

use Kirki\App\FormActions\FormActionDispatcher;
use Kirki\Framework\ServiceProvider;
use function Kirki\Framework\config;

class FormServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FormActionDispatcher::class, function () {
            return new FormActionDispatcher(config('form-actions.handlers', []));
        });
    }
}
