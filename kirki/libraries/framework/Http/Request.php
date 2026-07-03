<?php

/**
 * Handles REST API request data abstraction for operations.
 *
 * @package    Framework
 * @subpackage Http
 * @since      1.0.0
 */
namespace Kirki\Framework\Http;

\defined('ABSPATH') || exit;
use BadMethodCallException;
use Kirki\Framework\Contracts\Request as RequestContract;
use Kirki\Framework\Contracts\Support\Arrayable;
use Kirki\Framework\Sanitizer;
use Kirki\Framework\Exceptions\AuthorizationException;
use Kirki\Framework\Http\Concerns\InteractsWithFiles;
use Kirki\Framework\Supports\Arr;
use Kirki\Framework\Validation\Validator;
use WP_REST_Request;
use Kirki\Framework\Supports\Str;
use function Kirki\Framework\message;
use function Kirki\Framework\user;
/**
 * The Request class for handling HTTP requests.
 *
 * @method mixed|null string(string $key, $default = null)
 * @method mixed|null date(string $key, $default = null)
 * @method mixed|null datetime(string $key, $default = null)
 * @method mixed|null text(string $key, $default = null)
 * @method mixed|null html(string $key, $default = null)
 * @method mixed|null email(string $key, $default = null)
 * @method mixed|null url(string $key, $default = null)
 * @method mixed|null key(string $key, $default = null)
 * @method mixed|null title(string $key, $default = null)
 * @method mixed|null file_name(string $key, $default = null)
 * @method mixed|null mime_type(string $key, $default = null)
 * @method mixed|null int(string $key, $default = null)
 * @method mixed|null bool(string $key, $default = null)
 * @method mixed|null float(string $key, $default = null)
 * @method mixed|null array(string $key, $default = null)
 * @method mixed|null whitelisted(string $key, $default = null, array $whitelist = [])
 */
class Request implements RequestContract, Arrayable
{
    use InteractsWithFiles;
    /**
     * The request's input attributes.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $attributes = [];
    /**
     * The HTTP method used for the request (e.g. GET, POST).
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $method;
    /**
     * The route URI for the request.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $route;
    /**
     * The headers associated with the request.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $headers;
    /**
     * The sanitized data.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected array $sanitized = [];
    /**
     * The validated data.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected array $validated = [];
    /**
     * Whether the validation has been resolved.
     *
     * @var bool
     *
     * @since 1.0.0
     */
    protected bool $validation_resolved = \false;
    /**
     * Registered sanitizer method suffixes for typed request accessors.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     */
    protected static array $types = ['string', 'date', 'datetime', 'text', 'html', 'email', 'url', 'key', 'title', 'file_name', 'mime_type', 'int', 'bool', 'float', 'array', 'whitelisted'];
    /**
     * Magic getter to retrieve request attributes.
     *
     * @param string $name The name of the attribute.
     *
     * @return mixed|null The attribute value or null if not set.
     *
     * @since 1.0.0
     */
    public function __get(string $name)
    {
        return Arr::get($this->all(), $name, null);
    }
    /**
     * Magic isset to check if an attribute exists.
     *
     * @param string $name The name of the attribute.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function __isset(string $name)
    {
        return !\is_null($this->__get($name));
    }
    /**
     * Magic setter to set request attributes.
     *
     * @param string $name The name of the attribute.
     * @param mixed $value The value to assign.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __set(string $name, $value)
    {
        $this->attributes[$name] = $value;
    }
    /**
     * Dynamically handle calls to the class.
     *
     * @param string $name The name of the method.
     * @param array $arguments The arguments of the method.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function __call(string $name, array $arguments)
    {
        $name = \strtolower($name);
        if (!\in_array($name, static::$types, \true)) {
            throw new BadMethodCallException(\sprintf('Method %s::%s does not exist.', static::class, $name));
        }
        $method_name = 'get_' . $name;
        return $this->{$method_name}(...$arguments);
    }
    /**
     * Create a new Request instance from a WP_REST_Request.
     *
     * @param WP_REST_Request $request The WordPress REST request object.
     *
     * @return self
     *
     * @since 1.0.0
     */
    public static function from_wp_rest_request(WP_REST_Request $request)
    {
        return (new static())->make_request($request);
    }
    /**
     * Make a new request instance from a WP_REST_Request.
     *
     * @param WP_REST_Request $request The WordPress REST request object.
     *
     * @return self
     *
     * @since 1.0.0
     */
    public function make_request(WP_REST_Request $request)
    {
        $this->attributes = \array_merge($this->attributes, $request->get_params(), $request->get_file_params());
        $this->method = $request->get_method();
        $this->route = $request->get_route();
        $this->headers = $request->get_headers();
        return $this;
    }
    /**
     * Get the validation rules for the request.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function rules()
    {
        return [];
    }
    /**
     * Run the validation on the request data.
     *
     * @param array $data The data to validate.
     * @param array $rules The rules.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function run_validation(array $data, array $rules)
    {
        $validator = Validator::make($data, $rules, $this->messages());
        if ($validator->validate()) {
            return $validator->validated();
        }
    }
    /**
     * Define the sanitization filters for the request.
     * This will be defined into the extended request class.
     *
     * @return array<string,string|callable(mixed):mixed|array>
     *
     * @since 1.0.0
     */
    protected function filters()
    {
        return [];
    }
    /**
     * Define the validation messages for the request.
     * This will be defined into the extended request class.
     *
     * @return array<string,string|callable(mixed):mixed|array>
     *
     * @since 1.0.0
     */
    protected function messages()
    {
        return [];
    }
    /**
     * Run the sanitization on the data.
     *
     * @param array $data The data to sanitize.
     * @param array $filters The filters to apply.
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function run_sanitization(array $data, array $filters)
    {
        return Sanitizer::make($data, $filters)->get_sanitized_data();
    }
    /**
     * Get the current user instance from the request.
     *
     * @return \Framework\Wordpress\User
     *
     * @since 1.0.0
     */
    public function user()
    {
        return user();
    }
    /**
     * Get the HTTP method used in the request.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_method()
    {
        return $this->method;
    }
    /**
     * Alias of `get_method()`.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function method()
    {
        return $this->get_method();
    }
    /**
     * Get the route URI of the request.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_route()
    {
        return $this->route;
    }
    /**
     * Get the headers associated with the request.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function get_headers()
    {
        return $this->headers;
    }
    /**
     * Get a single HTTP header from the request.
     *
     * @param string $name The name.
     * @param mixed $default The default.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function get_header(string $name, $default = null)
    {
        $value = $this->headers[$name] ?? $default;
        if (\is_array($value)) {
            return $value[0] ?? $default;
        }
        return $value;
    }
    /**
     * Alias of `get_header()`.
     *
     * @param string $name The name.
     * @param mixed $default The default.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function header(string $name, $default = null)
    {
        return $this->get_header($name, $default);
    }
    /**
     * Get all input attributes.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function all()
    {
        return $this->attributes;
    }
    /**
     * Get the sanitized data.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function sanitized()
    {
        return $this->sanitized;
    }
    /**
     * Get the validated data.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function validated()
    {
        return $this->validated;
    }
    /**
     * Get all input attributes.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function attributes()
    {
        if ($this->has('_method')) {
            $this->remove('_method');
        }
        return $this->attributes;
    }
    /**
     * Authorize the request.
     *
     * @return static
     *
     * @throws AuthorizationException
     *
     * @since 1.0.0
     */
    public function authorize_request()
    {
        if (!$this->authorize()) {
            throw new AuthorizationException(message('auth.unauthorized_request'));
        }
        return $this;
    }
    /**
     * Validate the request data and sanitize the data.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public function validate_request()
    {
        if ($this->validation_resolved) {
            return $this;
        }
        $this->resolve_validation_and_sanitization();
        $this->validation_resolved = \true;
        return $this;
    }
    /**
     * Resolve the validation and sanitization.
     *
     * @return void
     *
     * @throws AuthorizationException
     *
     * @since 1.0.0
     */
    protected function resolve_validation_and_sanitization()
    {
        $this->prepare_for_validation();
        $sanitized = $this->run_sanitization($this->attributes(), $this->filters());
        $this->sanitized = $sanitized;
        $this->merge($sanitized);
        $validated = $this->run_validation($this->attributes(), $this->rules());
        $this->validated = $validated;
        $this->passed_validation();
    }
    /**
     * Prepare the request data for validation.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function prepare_for_validation()
    {
        // Override this method to prepare the request data for validation.
    }
    /**
     * Handle the passed validation.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function passed_validation()
    {
        // Override this method to handle the passed validation.
    }
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function authorize()
    {
        return \true;
    }
    /**
     * Merge the given input with the existing attributes.
     *
     * @param array $input The input to merge.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public function merge(array $input)
    {
        $this->attributes = \array_merge($this->attributes, $input);
        return $this;
    }
    /**
     * Check if an attribute exists.
     *
     * @param string $key The key of the attribute.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function has(string $key)
    {
        return isset($this->attributes[$key]);
    }
    /**
     * Remove an attribute.
     *
     * @param string $key The key of the attribute.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function remove(string $key)
    {
        unset($this->attributes[$key]);
    }
    /**
     * Get all input attributes except the specified keys.
     *
     * @param array $attributes The attribute keys to exclude.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function except(array $attributes)
    {
        return \array_diff_key($this->attributes, \array_flip($attributes));
    }
    /**
     * Get a single input attribute by key.
     *
     * @param string $key The key of the attribute.
     *
     * @return mixed|null
     *
     * @since 1.0.0
     */
    public function only(string $key)
    {
        return $this->attributes[$key] ?? null;
    }
    /**
     * Alias for the `only()` method.
     *
     * @param string $key The key of the attribute.
     *
     * @return mixed|null
     *
     * @since 1.0.0
     */
    public function input(string $key)
    {
        return $this->only($key);
    }
    /**
     * Get a value from the request with optional default and type casting.
     *
     * @param string $key The key to retrieve.
     * @param mixed $default Default value if the key doesn't exist.
     * @param string|null $type Optional type to cast the result to: 
     * int, float, bool, string, array with proper sanitization.
     *
     * @return mixed|null
     *
     * @since 1.0.0
     */
    public function get(string $key, $default = null, $type = null)
    {
        $value = isset($this->attributes[$key]) ? $this->attributes[$key] : $default;
        $value = Sanitizer::apply_rule($value, $type);
        return $value;
    }
    /**
     * Get a value from the request with optional default and type casting.
     *
     * @param string $key The key to retrieve.
     * @param mixed $default Default value if the key doesn't exist.
     * @param array $whitelist Optional whitelist of allowed values.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function get_whitelisted(string $key, $default = null, array $whitelist = [])
    {
        $value = $this->get($key);
        if (!\in_array($value, $whitelist, \true)) {
            return $default;
        }
        return $value;
    }
    /**
     * Get a string value with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_string(string $key, $default = null)
    {
        return $this->get($key, $default, Sanitizer::TEXT);
    }
    /**
     * Get a date value.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_date(string $key, $default = null)
    {
        return $this->get($key, $default, Sanitizer::DATE);
    }
    /**
     * Get a datetime value.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function get_datetime(string $key, $default = null)
    {
        return $this->get($key, $default, Sanitizer::DATETIME);
    }
    /**
     * Get a text with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_text(string $key, $default = null)
    {
        return $this->get_string($key, $default);
    }
    /**
     * Get a html supported content with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_html(string $key, $default = null)
    {
        return $this->get($key, $default, Sanitizer::RICH_TEXT);
    }
    /**
     * Get a email with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_email(string $key, $default = null)
    {
        return $this->get($key, $default, Sanitizer::EMAIL);
    }
    /**
     * Get a url with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_url(string $key, $default = null)
    {
        return $this->get($key, $default, Sanitizer::URL);
    }
    /**
     * Get a key value with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_key(string $key, $default = null)
    {
        return $this->get($key, $default, Sanitizer::KEY);
    }
    /**
     * Get a title value with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_title(string $key, $default = null)
    {
        return $this->get($key, $default, Sanitizer::TITLE);
    }
    /**
     * Get a file name with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_file_name(string $key, $default = null)
    {
        return $this->get($key, $default, Sanitizer::FILE_NAME);
    }
    /**
     * Get mime type with sanitization applied.
     *
     * @param string $key The key to retrieve.
     * @param string|null $default Default value if the key doesn't exist.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function get_mime_type(string $key, $default = null)
    {
        return $this->get($key, $default, Sanitizer::MIME_TYPE);
    }
    /**
     * Get an integer value.
     *
     * @param string $key The key to retrieve.
     * @param int|null $default Default value if the key doesn't exist.
     *
     * @return int|null
     *
     * @since 1.0.0
     */
    public function get_int(string $key, $default = null)
    {
        return $this->get($key, $default, Sanitizer::INT);
    }
    /**
     * Get a boolean value.
     *
     * @param string $key The key to retrieve.
     * @param bool $default Default value if the key doesn't exist.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function get_bool(string $key, bool $default = \false)
    {
        return $this->get($key, $default, Sanitizer::BOOL);
    }
    /**
     * Get a float value.
     *
     * @param string $key The key to retrieve.
     * @param float|null $default Default value if the key doesn't exist.
     *
     * @return float|null
     *
     * @since 1.0.0
     */
    public function get_float(string $key, $default = null)
    {
        return $this->get($key, $default, Sanitizer::FLOAT);
    }
    /**
     * Get an array value.
     *
     * @param string $key The key to retrieve.
     * @param array|null $default Default value if the key doesn't exist.
     *
     * @return array|null
     *
     * @since 1.0.0
     */
    public function get_array(string $key, $default = null)
    {
        return $this->get($key, $default, Sanitizer::ARRAY);
    }
    /**
     * Convert the request to an array.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function to_array()
    {
        return $this->all();
    }
}
