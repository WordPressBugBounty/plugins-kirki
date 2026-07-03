<?php

/**
 * Fluent REST API route registrar for WordPress with middleware, grouping, and controller binding.
 * Resolves controller actions via reflection and dependency injection from the container.
 * Registers routes on rest_api_init through RegisterRestApi.
 *
 * @package Framework
 * @since   1.0.0
 */
namespace Kirki\Framework;

\defined('ABSPATH') || exit;
use Closure;
use Kirki\Framework\Contracts\Request as RequestContract;
use Kirki\Framework\Database\Query\Model;
use Exception;
use Kirki\Framework\Collections\Collection;
use Kirki\Framework\Contracts\Middleware;
use Kirki\Framework\Exceptions\AuthorizationException;
use Kirki\Framework\Exceptions\InvalidRoutActionException;
use Kirki\Framework\Exceptions\ModelNotFoundException;
use Kirki\Framework\Http\Request;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use WP_Error;
use WP_REST_Request;
use function Kirki\Framework\app;
use function Kirki\Framework\Polyfill\array_first;
use function Kirki\Framework\Polyfill\array_last;
class Route
{
    /**
     * REST API namespace.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected static $namespace = '';
    /**
     * Array of registered routes.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected static $routes = [];
    /**
     * Group stack to hold the group options.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected static $group_stack = [];
    /**
     * HTTP method for the route.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $method;
    /**
     * The endpoint path for the route.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $endpoint;
    /**
     * Controller class and method for handling the route.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $action;
    /**
     * Array of middleware classes.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $middlewares = [];
    /**
     * Regex patterns.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $patterns = [];
    /**
     * Array of class instances.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected static $instances = [];
    /**
     * The resolved request.
     *
     * @var Request
     *
     * @since 1.0.0
     */
    protected $resolved_request;
    /**
     * Set the API namespace for all registered routes.
     *
     * @param string $namespace The namespace for REST API routes.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function set_namespace(string $namespace)
    {
        static::$namespace = $namespace;
    }
    /**
     * Get the API namespace.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function get_namespace()
    {
        return static::$namespace;
    }
    /**
     * Get the URL for a specific route.
     *
     * @param string $path The route path.
     *
     * @return string The URL for the route.
     *
     * @since 1.0.0
     */
    public static function url(string $path)
    {
        return rest_url('/' . static::$namespace . '/' . $path);
    }
    /**
     * Attach middleware to the current route.
     *
     * @param string|array $middleware The fully qualified class name of the middleware.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function middleware($middleware)
    {
        if (\is_array($middleware)) {
            $this->middlewares = \array_merge($this->middlewares, $middleware);
            return $this;
        }
        $this->middlewares[] = $middleware;
        return $this;
    }
    /**
     * Set a regex pattern for the specific route param.
     *
     * @param string $name The name.
     * @param string $regex The regex.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public function where(string $name, string $regex)
    {
        $this->patterns[$name] = $regex;
        return $this;
    }
    /**
     * Get the endpoint in proper format that register_rest_route() expects.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function get_formatted_endpoint()
    {
        return \preg_replace_callback('/\\{(\\w+)\\}/', function ($matches) {
            $param = $matches[1];
            $pattern = isset($this->patterns[$param]) ? $this->patterns[$param] : '[^/]+';
            return '(?P<' . $param . '>' . $pattern . ')';
        }, $this->endpoint);
    }
    /**
     * Register a GET route.
     *
     * @param string $endpoint The route endpoint.
     * @param array|Closure $action The controller and method to handle the route.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public static function get(string $endpoint, $action)
    {
        $instance = new static();
        $instance->method = 'get';
        $instance->endpoint = $endpoint;
        $instance->action = $action;
        $instance->apply_group_options();
        static::$routes[] = $instance;
        return $instance;
    }
    /**
     * Register a POST route.
     *
     * @param string $endpoint The route endpoint.
     * @param array|Closure $action The controller and method to handle the route.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public static function post(string $endpoint, $action)
    {
        $instance = new static();
        $instance->method = 'post';
        $instance->endpoint = $endpoint;
        $instance->action = $action;
        $instance->apply_group_options();
        static::$routes[] = $instance;
        return $instance;
    }
    /**
     * Register a PUT route.
     *
     * @param string $endpoint The route endpoint.
     * @param array|Closure $action The controller and method to handle the route.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public static function put(string $endpoint, $action)
    {
        $instance = new static();
        $instance->method = 'put';
        $instance->endpoint = $endpoint;
        $instance->action = $action;
        $instance->apply_group_options();
        static::$routes[] = $instance;
        return $instance;
    }
    /**
     * Register a PATCH route.
     *
     * @param string $endpoint The route endpoint.
     * @param array|Closure $action The controller and method to handle the route.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public static function patch(string $endpoint, $action)
    {
        $instance = new static();
        $instance->method = 'patch';
        $instance->endpoint = $endpoint;
        $instance->action = $action;
        $instance->apply_group_options();
        static::$routes[] = $instance;
        return $instance;
    }
    /**
     * Register a DELETE route.
     *
     * @param string $endpoint The route endpoint.
     * @param array|Closure $action The controller and method to handle the route.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public static function delete(string $endpoint, $action)
    {
        $instance = new static();
        $instance->method = 'delete';
        $instance->endpoint = $endpoint;
        $instance->action = $action;
        $instance->apply_group_options();
        static::$routes[] = $instance;
        return $instance;
    }
    /**
     * Register a group of routes with shared options.
     *
     * This method allows grouping routes under common configuration options
     * like middleware, or prefix. The closure receives the context
     * of the group and defines the routes within it.
     *
     * @param array $options The shared configuration options for the group.
     * @param \Closure $closure The callback that defines the grouped routes.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function group(array $options, Closure $closure)
    {
        static::$group_stack[] = $options;
        $closure();
        \array_pop(static::$group_stack);
    }
    /**
     * Get all registered routes.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function get_routes()
    {
        return static::$routes;
    }
    /**
     * Apply route group options like prefix and middleware to the route.
     *
     * This method is typically called when a route is defined within a group,
     * applying any shared prefix or middleware from the group stack.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function apply_group_options()
    {
        if (!empty(static::$group_stack)) {
            $group = array_last(static::$group_stack);
            if (!empty($group['prefix'])) {
                $this->endpoint = \rtrim($group['prefix'], '/') . '/' . \ltrim($this->endpoint, '/');
            }
            if (!empty($group['middleware'])) {
                $this->middleware($group['middleware']);
            }
        }
    }
    /**
     * Register the route with WordPress.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function register()
    {
        register_rest_route(static::$namespace, $this->get_formatted_endpoint(), ['methods' => \strtoupper($this->method), 'callback' => $this->resolve_route(), 'permission_callback' => fn($rest_request) => $this->resolve_permission_callback($rest_request)]);
    }
    /**
     * Cache a class instance.
     *
     * @param string $abstract The class name to bind
     * @param object $instance The instance of the class
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function cache(string $abstract, $instance)
    {
        static::$instances[$abstract] = $instance;
    }
    /**
     * Check if a class instance is cached.
     *
     * @param string $abstract The class name to check
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function is_cached(string $abstract)
    {
        return isset(static::$instances[$abstract]);
    }
    /**
     * Get a cached class instance.
     *
     * @param string $abstract The class name to get
     *
     * @return object
     *
     * @since 1.0.0
     */
    protected function get_cached(string $abstract)
    {
        return static::$instances[$abstract];
    }
    /**
     * Resolve a class and its dependencies.
     *
     * @param string $abstract The class name to resolve
     * @param array $resolving Stack of classes being resolved (for
     *
     * @return object The resolved instance
     *
     * @throws \Exception
     *
     * @since 1.0.0
     */
    protected function make(string $abstract, array $resolving = [])
    {
        if ($this->is_cached($abstract)) {
            return $this->get_cached($abstract);
        }
        if (\in_array($abstract, $resolving, \true)) {
            throw new Exception(\sprintf('Circular dependency detected for class "%s".', $abstract));
        }
        if (!\class_exists($abstract)) {
            throw new Exception(\sprintf('Class "%s" does not exist.', $abstract));
        }
        $reflector = new ReflectionClass($abstract);
        if ($reflector->isAbstract()) {
            throw new Exception(\sprintf('Class "%s" is abstract and cannot be instantiated.', $abstract));
        }
        $constructor = $reflector->getConstructor();
        if (!$constructor) {
            return new $abstract();
        }
        if (!$constructor->isPublic()) {
            throw new Exception(\sprintf('Class "%s" has a non-public constructor and cannot be instantiated.', $abstract));
        }
        $dependencies = [];
        $resolving[] = $abstract;
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (!$type) {
                throw new Exception(\sprintf('Parameter "%s" is missing a type hint in the constructor. Please add a class type hint.', $parameter->getName()));
            }
            if ($type->isBuiltin()) {
                throw new Exception(\sprintf(
                    'Parameter "%s" must be a class type, not a built-in type. Please specify a valid class dependency.',
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    $parameter->getName()
                ));
            }
            $dependencies[] = $this->is_cached($type->getName()) ? $this->get_cached($type->getName()) : $this->make($type->getName(), $resolving);
        }
        $instance = $reflector->newInstanceArgs($dependencies);
        $this->cache($abstract, $instance);
        return $instance;
    }
    /**
     * Make the method dependencies.
     *
     * @param string $abstract The class name to make the dependencies.
     * @param string $method The method name to make the dependencies.
     *
     * @return array
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     *
     * @since 1.0.0
     */
    protected function resolve_method_dependencies($abstract, $method)
    {
        $method_reflection = new ReflectionMethod($abstract, $method);
        if (!$method_reflection->isPublic()) {
            throw new Exception(\sprintf('Method "%s" is not public and cannot be called.', $method));
        }
        $parameters = $method_reflection->getParameters();
        $dependencies = ['requests' => [], 'builtins' => [], 'models' => [], 'abstracts' => []];
        foreach ($parameters as $parameter) {
            $type = $parameter->getType() ?? 'string';
            $variable = $parameter->getName();
            $position = $parameter->getPosition();
            $type_name = $type instanceof ReflectionNamedType ? $type->getName() : (string) $type;
            if ($type === 'string' || $type->isBuiltin()) {
                $dependencies['builtins'][] = $this->add_dependency($type_name, $variable, $position);
            } elseif ($type_name === Request::class || $type_name === RequestContract::class || \is_subclass_of($type_name, Request::class)) {
                // phpcs:ignore Generic.Files.LineLength.TooLong
                $dependencies['requests'][] = $this->add_dependency($type_name, $variable, $position);
            } elseif (\is_subclass_of($type_name, Model::class)) {
                $dependencies['models'][] = $this->add_dependency($type_name, $variable, $position);
            } else {
                $dependencies['abstracts'][] = $this->add_dependency($type_name, $variable, $position);
            }
        }
        if (\count($dependencies['requests']) < 1) {
            throw new InvalidArgumentException(\sprintf('The method "%s" must have at least one request dependency.', $method));
        }
        if (\count($dependencies['requests']) > 1) {
            throw new InvalidArgumentException(\sprintf('The method "%s" must have only one request dependency.', $method));
        }
        return $dependencies;
    }
    /**
     * Add a dependency to the dependencies array.
     *
     * @param string $type The type of the dependency.
     * @param string $variable The variable name of the dependency.
     * @param int $position The position of the dependency.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function add_dependency($type, $variable, $position)
    {
        return \compact('type', 'variable', 'position');
    }
    /**
     * Add a resolved dependency to the dependencies array.
     *
     * @param mixed $resolved The resolved dependency.
     * @param int $position The position of the dependency.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function add_resolved_dependency($resolved, int $position)
    {
        return \compact('resolved', 'position');
    }
    /**
     * Resolve the models.
     *
     * @param array $models The models to resolve.
     * @param Request $request The request object.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function resolve_models(array $models, Request $request)
    {
        $resolved_models = [];
        foreach ($models as $model) {
            $position = $model['position'];
            $type = $model['type'];
            $variable = $model['variable'];
            $value = $request->get($variable);
            $model = $this->resolve_model($type, $value);
            $resolved_models[] = $this->add_resolved_dependency($model, $position);
        }
        return $resolved_models;
    }
    /**
     * Resolve the built-in types.
     *
     * @param array $builtins The built-in types to resolve.
     * @param Request $request The request object.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function resolve_builtins(array $builtins, Request $request)
    {
        $resolved_builtins = [];
        foreach ($builtins as $builtin) {
            $type = $builtin['type'];
            $variable = $builtin['variable'];
            $position = $builtin['position'];
            $value = $request->get($variable, null, $type);
            $resolved_builtins[] = $this->add_resolved_dependency($value, $position);
        }
        return $resolved_builtins;
    }
    /**
     * Resolve the abstracts.
     *
     * @param array $abstracts The abstracts to resolve.
     * @param Request $request The request object.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function resolve_abstracts(array $abstracts, Request $request)
    {
        $resolved_abstracts = [];
        foreach ($abstracts as $abstract) {
            $position = $abstract['position'];
            $resolved = app()->make($abstract['type']);
            $resolved_abstracts[] = $this->add_resolved_dependency($resolved, $position);
        }
        return $resolved_abstracts;
    }
    /**
     * Resolve a model from the request.
     *
     * @param class-string<Model> $model The model class name
     * @param mixed $value The value of the model
     *
     * @return Model
     *
     * @since 1.0.0
     */
    protected function resolve_model($model, $value)
    {
        $key_name = (new $model())->get_route_key();
        try {
            return $model::where($key_name, $value)->first_or_fail();
        } catch (ModelNotFoundException $exception) {
            $exception->set_model($model);
            $exception->set_ids($value);
            throw $exception;
        }
    }
    /**
     * Resolve the route handler.
     *
     * @return callable
     *
     * @throws InvalidRoutActionException
     *
     * @since 1.0.0
     */
    protected function resolve_route()
    {
        return $this->action instanceof Closure ? $this->resolve_closure_action() : $this->resolve_controller_action();
    }
    /**
     * Resolve the closure route action.
     *
     * @return callable
     *
     * @since 1.0.0
     */
    protected function resolve_closure_action()
    {
        return function ($rest_request) {
            try {
                $request = $this->get_resolved_request($rest_request);
                return ($this->action)($request);
            } catch (Exception $exception) {
                return ApiExceptionHandler::get_response($exception);
            }
        };
    }
    /**
     * Resolve the controller route action.
     *
     * @return callable
     *
     * @since 1.0.0
     */
    protected function resolve_controller_action()
    {
        return function ($rest_request) {
            try {
                return $this->dispatch_controller($rest_request);
            } catch (Exception $exception) {
                return ApiExceptionHandler::get_response($exception);
            }
        };
    }
    /**
     * Dispatch the controller action with the middleware-enriched request.
     *
     * @param WP_REST_Request $rest_request The REST request object.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    protected function dispatch_controller($rest_request)
    {
        $request = $this->get_resolved_request($rest_request);
        $controller = $this->resolve_controller($request);
        $dependecies = $this->update_request($controller['dependencies'], $this->add_resolved_dependency($request, $controller['request_position']));
        $dependecies = $this->sort_dependencies($dependecies);
        $parameters = (new Collection($dependecies))->pluck('resolved')->all();
        $instance = $controller['instance'];
        $method = $controller['method'];
        return $instance->{$method}(...$parameters);
    }
    /**
     * Resolve the controller for the route.
     *
     * @param Request $request The middleware-enriched request object.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function resolve_controller(Request $request)
    {
        if (!\is_array($this->action)) {
            throw new InvalidRoutActionException(\sprintf('Invalid method registered for the route %s', $this->endpoint));
        }
        if (\count($this->action) !== 2) {
            throw new InvalidRoutActionException(\sprintf('Invalid controller syntax for the route %s', $this->endpoint));
        }
        [$controller, $method] = $this->action;
        if (!\class_exists($controller)) {
            throw new InvalidRoutActionException(\sprintf('Controller %s not found', $controller));
        }
        $controller_instance = $this->make($controller);
        if (!\method_exists($controller_instance, $method)) {
            throw new InvalidRoutActionException(\sprintf('The method %s is missing in the controller %s', $method, $controller));
        }
        $dependencies = $this->resolve_method_dependencies($controller_instance, $method);
        $first_request = array_first($dependencies['requests']);
        $request_position = $first_request['position'];
        $dependency_array = $this->resolve_dependencies($dependencies, $request);
        return ['instance' => $controller_instance, 'method' => $method, 'request' => $request, 'dependencies' => $dependency_array, 'request_position' => $request_position];
    }
    /**
     * Build the middleware pipeline.
     *
     * @param callable $destination The destination callback.
     *
     * @return callable
     *
     * @since 1.0.0
     */
    protected function build_middleware_pipeline(callable $destination)
    {
        return \array_reduce(\array_reverse($this->middlewares), function ($next, $middleware) {
            return function ($request) use($next, $middleware) {
                if (!\is_subclass_of($middleware, Middleware::class)) {
                    throw new InvalidArgumentException(\sprintf('Middleware %s must implement the %s interface.', $middleware, Middleware::class));
                }
                return (new $middleware())->handle($request, $next);
            };
        }, $destination);
    }
    /**
     * Resolve the permission callback for the route.
     *
     * @param WP_REST_Request $rest_request The REST request object.
     *
     * @return bool|WP_Error
     *
     * @since 1.0.0
     */
    protected function resolve_permission_callback($rest_request)
    {
        $request = $this->make_framework_request($rest_request);
        try {
            $request->authorize_request();
            if (empty($this->middlewares)) {
                $this->resolved_request = $this->expose($request);
                return \true;
            }
            $pipeline = $this->build_middleware_pipeline(fn($request) => \true);
            $pipeline($request);
            $this->resolved_request = $this->expose($request);
            return \true;
        } catch (AuthorizationException $exception) {
            return new WP_Error('rest_forbidden', $exception->getMessage(), ['status' => $exception->getCode()]);
        }
    }
    /**
     * Create a framework request from a WordPress REST request.
     *
     * @param WP_REST_Request $rest_request The REST request object.
     *
     * @return Request
     *
     * @since 1.0.0
     */
    protected function make_framework_request(WP_REST_Request $rest_request)
    {
        $request_class = $this->resolve_request_class();
        return app()->make($request_class)->make_request($rest_request);
    }
    /**
     * Resolve the request class from the route action.
     *
     * @return class-string<Request>
     *
     * @since 1.0.0
     */
    protected function resolve_request_class()
    {
        if ($this->action instanceof Closure) {
            return $this->resolve_closure_request_class($this->action);
        }
        if (!\is_array($this->action) || \count($this->action) !== 2) {
            return Request::class;
        }
        [$controller, $method] = $this->action;
        if (!\class_exists($controller) || !\method_exists($controller, $method)) {
            return Request::class;
        }
        $dependencies = $this->resolve_method_dependencies($controller, $method);
        $first_request = array_first($dependencies['requests']);
        return $this->normalize_request_class($first_request['type']);
    }
    /**
     * Resolve the request class from a closure route action.
     *
     * @param Closure $closure The closure route action.
     *
     * @return class-string<Request>
     *
     * @since 1.0.0
     */
    protected function resolve_closure_request_class(Closure $closure)
    {
        $reflection = new ReflectionFunction($closure);
        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }
            $type_name = $type->getName();
            if ($type_name === Request::class || $type_name === RequestContract::class || \is_subclass_of($type_name, Request::class)) {
                return $this->normalize_request_class($type_name);
            }
        }
        return Request::class;
    }
    /**
     * Normalize a reflected request type to a concrete request class.
     *
     * @param string $type_name The reflected request type name.
     *
     * @return class-string<Request>
     *
     * @since 1.0.0
     */
    protected function normalize_request_class($type_name)
    {
        if ($type_name === RequestContract::class) {
            return Request::class;
        }
        return $type_name;
    }
    /**
     * Get the request enriched by middleware during permission checking.
     *
     * @param WP_REST_Request $rest_request The REST request object.
     *
     * @return Request
     *
     * @since 1.0.0
     */
    protected function get_resolved_request(WP_REST_Request $rest_request)
    {
        if (!\is_null($this->resolved_request)) {
            return $this->resolved_request->validate_request();
        }
        $request = $this->expose($this->make_framework_request($rest_request));
        return $request->validate_request();
    }
    /**
     * Expose the request to the container to use
     * the current request instance to the underneath classes and methods.
     *
     * @param Request $request The request object.
     *
     * @return Request
     *
     * @since 1.0.0
     */
    protected function expose(Request $request)
    {
        app()->instance('request', $request);
        return $request;
    }
    /**
     * Prepare the dependencies for the route. This will resolved the models,
     * abstract classes like services, repositories, built-in types and requests.
     * We are not appending the requests to the dependencies array because we will resolve them later
     * after all the middlewares are handled.
     *
     * @param array $dependencies The dependencies of the route.
     * @param Request $request The request object.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function resolve_dependencies(array $dependencies, Request $request)
    {
        $models = $this->resolve_models($dependencies['models'], $request);
        $builtins = $this->resolve_builtins($dependencies['builtins'], $request);
        $abstracts = $this->resolve_abstracts($dependencies['abstracts'], $request);
        return \array_values(\array_merge($models, $builtins, $abstracts));
    }
    /**
     * Update the dependencies array with the resolved request.
     * Here we are attaching the request with the dependencies.
     * And this request is the request object after passing all the middlewares.
     *
     * @param array $dependencies The dependencies of the route.
     * @param array $resolved_request The resolved request.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function update_request(array $dependencies, array $resolved_request)
    {
        return \array_merge($dependencies, [$resolved_request]);
    }
    /**
     * Sort the dependencies array by position so that it matches the original sequence of the dependencies.
     *
     * @param array $dependencies The dependencies of the route.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function sort_dependencies(array $dependencies)
    {
        \usort($dependencies, function ($first, $second) {
            return $first['position'] - $second['position'];
        });
        return $dependencies;
    }
}
