<?php

/**
 * Abstract base for application service providers with register and boot lifecycle hooks.
 * Receives the Application instance and binds services in register while wiring hooks in boot.
 * Pattern for modular plugin feature registration.
 *
 * @package Framework
 * @since   1.0.0
 */
namespace Kirki\Framework;

\defined('ABSPATH') || exit;
abstract class ServiceProvider
{
    /**
     * The app.
     *
     * @var Application
     *
     * @since 1.0.0
     */
    protected $app;
    /**
     * Create a new service provider constructor
     *
     * @param Application $app The app.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    /**
     * Register the service provider.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public abstract function register();
    /**
     * Boot the service provider.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function boot()
    {
        //
    }
}
