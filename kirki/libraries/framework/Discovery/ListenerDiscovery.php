<?php

/**
 * Scans app/Listeners for event listener classes and maps them to event types via reflection.
 * Skips filesystem discovery in production when a cache file exists.
 * Writes a listeners.cache.php config file for fast EventManager boot.
 *
 * @package    Framework
 * @subpackage Discovery
 * @since      1.0.0
 */
namespace Kirki\Framework\Discovery;

\defined('ABSPATH') || exit;
use Kirki\Framework\Contracts\Cacheable;
use Kirki\Framework\Contracts\Discoverable;
use Kirki\Framework\Supports\Facades\File;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use function Kirki\Framework\app;
use function Kirki\Framework\app_path;
use function Kirki\Framework\collection;
use function Kirki\Framework\config_path;
class ListenerDiscovery implements Discoverable, Cacheable
{
    /**
     * The discovered listeners array .
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected array $listeners = [];
    /**
     * Discover the listeners from the file system.
     *
     * @return self
     *
     * @since 1.0.0
     */
    public function discover()
    {
        // For the production mode we will cache the listeners to improve the performance.
        // So don't need to discover the listeners from the filesystem.
        // In the production mode we have to run the discovery once so that it does not miss any listeners.
        if (!app()->is_dev_mode()) {
            return $this->discover_once();
        }
        return $this->create_cache_file();
    }
    /**
     * Discover the listeners from the cache.
     *
     * @return self
     *
     * @since 1.0.0
     */
    protected function discover_once()
    {
        $listeners_cache_path = config_path('listeners.cache.php');
        if (!\file_exists($listeners_cache_path)) {
            return $this;
        }
        return $this->create_cache_file();
    }
    /**
     * Create the cache file.
     *
     * @return self
     *
     * @since 1.0.0
     */
    protected function create_cache_file()
    {
        $listeners_directory = app_path('Listeners');
        if (!\file_exists($listeners_directory)) {
            return $this;
        }
        $listeners_files = \glob($listeners_directory . '/*.php');
        if (empty($listeners_files)) {
            return $this;
        }
        foreach ($listeners_files as $file) {
            $listener = $this->listener_class($this->filename($file));
            if (!$this->is_valid_listener($listener)) {
                continue;
            }
            $event = $this->event_class($listener);
            if (\is_null($event)) {
                continue;
            }
            $priority = $this->priority($listener);
            $this->listeners[$event][] = \compact('listener', 'priority');
        }
        return $this;
    }
    /**
     * Get the listeners array.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function listeners()
    {
        return $this->prepare($this->listeners);
    }
    /**
     * Get the priority of the listener.
     *
     * @param string $listener The listener.
     *
     * @return int
     *
     * @since 1.0.0
     */
    protected function priority(string $listener)
    {
        $reflection = new ReflectionClass($listener);
        $method = $reflection->getMethod('priority');
        return $method->invoke($reflection->newInstanceWithoutConstructor());
    }
    /**
     * Get the event class of the listener.
     *
     * @param string $listener The listener.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    protected function event_class(string $listener)
    {
        $method = new ReflectionMethod($listener, 'handle');
        $parameters = $method->getParameters();
        if (empty($parameters) || \count($parameters) !== 1) {
            return null;
        }
        $parameter = $parameters[0];
        $type = $parameter->getType();
        $type_name = $type instanceof ReflectionNamedType ? $type->getName() : (string) $type;
        if (!\class_exists($type_name)) {
            return null;
        }
        return $type_name;
    }
    /**
     * Get the filename of the listener.
     *
     * @param string $file The file.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function filename(string $file)
    {
        return \basename($file, '.php');
    }
    /**
     * Get the listener class of the filename.
     *
     * @param string $filename The filename.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function listener_class(string $filename)
    {
        $app_namespace = \rtrim(app()->get_namespace(), '\\');
        $namespace = $app_namespace . '\\Listeners\\';
        return $namespace . $filename;
    }
    /**
     * Check if the listener is valid.
     *
     * @param string $listener The listener.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function is_valid_listener(string $listener)
    {
        if (!\class_exists($listener)) {
            return \false;
        }
        $reflection = new ReflectionClass($listener);
        if (!$reflection->hasMethod('handle')) {
            return \false;
        }
        return \true;
    }
    /**
     * Cache the listeners.
     *
     * @param ?string $path The path.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function cache(?string $path = null)
    {
        $path = $path ?? config_path('listeners.cache.php');
        if (!$this->is_cacheable($path)) {
            return $this;
        }
        File::put($path, "<?php \r\n\r\n/** Auto generated by discovering the listeners. Don't edit this file manually." . " */\r\n\r\nreturn " . \var_export($this->listeners(), \true) . ';');
        return $this;
    }
    /**
     * Check if the listeners are cacheable.
     *
     * @param string $path The path.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function is_cacheable(string $path)
    {
        return app()->is_dev_mode() || File::missing($path);
    }
    /**
     * Prioritize the listeners.
     *
     * @param array $listeners The listeners.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function prioritize(array $listeners)
    {
        \usort($listeners, function ($first, $second) {
            return $second['priority'] - $first['priority'];
        });
        return $listeners;
    }
    /**
     * Prepare the listeners.
     *
     * @param array $listeners The listeners.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function prepare(array $listeners)
    {
        $prepared = [];
        foreach ($listeners as $event => $listener) {
            $prepared[$event] = collection($this->prioritize($listener))->pluck('listener')->all();
        }
        return $prepared;
    }
}
