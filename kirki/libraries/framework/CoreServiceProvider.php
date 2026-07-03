<?php

/**
 * Registers the framework's essential services during application bootstrap.
 * Binds database, schema, migration, discovery, and manager components into the container.
 * Boots listener and policy discovery caching.
 *
 * @package Framework
 * @since   1.0.0
 */
namespace Kirki\Framework;

\defined('ABSPATH') || exit;
use Kirki\Framework\Console\CommandManager;
use Kirki\Framework\Database\Connection\Connection;
use Kirki\Framework\Database\Connection\DatabaseManager;
use Kirki\Framework\Database\Migrations\Migrator;
use Kirki\Framework\Database\Schema\SchemaManager;
use Kirki\Framework\Discovery\ListenerDiscovery;
use Kirki\Framework\Discovery\PolicyDiscovery;
use Kirki\Framework\Managers\EventManager;
use Kirki\Framework\Managers\LogManager;
use Kirki\Framework\Managers\PolicyManager;
use Kirki\Framework\ServiceProvider;
use Kirki\Framework\Managers\DateManager;
use Kirki\Framework\Http\Response;
use Kirki\Framework\Supports\MessagesBag;
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register the hooks to the application.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function register()
    {
        $this->register_database_services();
        $this->register_discoveries();
        $this->register_managers();
        $this->register_migrations();
        $this->register_messages();
        $this->app->singleton(Response::class);
        if (\class_exists(\Faker\Factory::class)) {
            $this->app->singleton(\Faker\Factory::class, function () {
                return \Faker\Factory::create();
            });
        }
    }
    /**
     * Boot the service provider.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function boot()
    {
        $this->app->make(PolicyDiscovery::class)->discover()->cache();
        $this->app->make(ListenerDiscovery::class)->discover()->cache();
    }
    /**
     * Register the messages.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function register_messages()
    {
        $this->app->singleton(MessagesBag::class, function () {
            return new MessagesBag();
        });
    }
    /**
     * Register the managers.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function register_managers()
    {
        $this->app->singleton(DatabaseManager::class);
        $this->app->singleton(SchemaManager::class);
        $this->app->singleton(LogManager::class);
        $this->app->singleton(EventManager::class);
        $this->app->singleton(PolicyManager::class);
        $this->app->singleton(DateManager::class);
        $this->app->singleton(CommandManager::class);
    }
    /**
     * Register the discoveries.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function register_discoveries()
    {
        $this->app->singleton(ListenerDiscovery::class);
        $this->app->singleton(PolicyDiscovery::class);
    }
    /**
     * Register the database singletons.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function register_database_services()
    {
        $this->app->singleton(Connection::class);
        $this->app->singleton(Migrator::class);
    }
    /**
     * Register the migrations tags.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function register_migrations()
    {
        $migrations_path = $this->app->config_path('migrations.php');
        if (!\file_exists($migrations_path)) {
            return;
        }
        $migrations = (include $migrations_path);
        if (empty($migrations)) {
            return;
        }
        $this->app->tag($migrations, 'app.migrations');
    }
}
