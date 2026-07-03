<?php

namespace Kirki\App\Providers;

defined( 'ABSPATH' ) || exit;

use Kirki\App\Managers\GlobalDataManager;
use Kirki\App\Managers\PageManager;
use Kirki\App\Managers\SymbolManager;
use Kirki\Framework\ServiceProvider;

class FacadeProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(PageManager::class, function() {
            return new PageManager();
        });

        $this->app->singleton(GlobalDataManager::class, function() {
            return new GlobalDataManager();
        });

        $this->app->singleton(SymbolManager::class, function() {
            return new SymbolManager();
        });
    }
}