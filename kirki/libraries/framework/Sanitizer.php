<?php

/**
 * Central registry of named sanitization strategies mapped to WordPress and custom cleaners.
 * Defines constants for text, email, url, rich-text, and structured types with a dispatch method per rule name.
 * Shared by validation and request sanitization pipelines.
 *
 * @package Framework
 * @since   1.0.0
 */
namespace Kirki\Framework;

\defined('ABSPATH') || exit;
use Kirki\Framework\Supports\Facades\Date;
use function Kirki\Framework\is_valid_json;
class Sanitizer
{
    /**
     * Trim the value.
     *
     * @var string
     */
    public const ANY = 'any';
    /**
     * Trim the value.
     *
     * @var string
     */
    public const TRIM = 'trim';
    /**
     * Sanitize the value as text.
     *
     * @var string
     */
    public const TEXT = 'text';
    /**
     * Sanitize the rich text content.
     *
     * @var string
     */
    public const RICH_TEXT = 'rich-text';
    /**
     * Sanitize the value as textarea.
     *
     * @var string
     */
    public const TEXTAREA = 'textarea';
    /**
     * Sanitize the value as email.
     *
     * @var string
     */
    public const EMAIL = 'email';
    /**
     * Sanitize the value as username.
     *
     * @var string
     */
    public const USERNAME = 'username';
    /**
     * Sanitize the value as url.
     *
     * @var string
     */
    public const URL = 'url';
    /**
     * Sanitize the value as key.
     *
     * @var string
     */
    public const KEY = 'key';
    /**
     * Sanitize the value as title.
     *
     * @var string
     */
    public const TITLE = 'title';
    /**
     * Sanitize the value as file name.
     *
     * @var string
     */
    public const FILE_NAME = 'file-name';
    /**
     * Sanitize the value as mime type.
     *
     * @var string
     */
    public const MIME_TYPE = 'mime-type';
    /**
     * Sanitize the value as int.
     *
     * @var string
     */
    public const INT = 'int';
    /**
     * Sanitize the value as int.
     *
     * @var string
     */
    public const FLOAT = 'float';
    /**
     * Sanitize the value as double.
     *
     * @var string
     */
    public const DOUBLE = 'double';
    /**
     * Sanitize the value as boolean.
     *
     * @var string
     */
    public const BOOL = 'bool';
    /**
     * Sanitize the value as array.
     *
     * @var string
     */
    public const ARRAY = 'array';
    /**
     * Sanitize the value as date.
     *
     * @var string
     */
    public const DATE = 'date';
    /**
     * Sanitize the value as datetime.
     *
     * @var string
     */
    public const DATETIME = 'datetime';
    /**
     * Sanitize the value as serialized.
     *
     * @var string
     */
    public const SERIALIZED = 'serialized';
    /**
     * Sanitize the value as unserialized.
     *
     * @var string
     */
    public const UNSERIALIZED = 'unserialized';
    /**
     * Input data.
     *
     * @var array<key:int,value:mixed>
     *
     * @since 1.0.0
     */
    protected $data;
    /**
     * Sanitized data.
     *
     * @var array<key:int,value:mixed>
     *
     * @since 1.0.0
     */
    protected $sanitized_data = [];
    /**
     * Sanitization rules.
     *
     * @var array<key:int,value:mixed>
     *
     * @since 1.0.0
     */
    protected $rules;
    /**
     * Create a new Sanitizer instance.
     *
     * @param array $data The data payload.
     * @param array $rules The rules.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct(array $data = [], array $rules = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->run_sanitizer();
    }
    /**
     * Get the sanitized data.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function get_sanitized_data()
    {
        return $this->sanitized_data;
    }
    /**
     * Sanitize the data.
     *
     * @param array $data The data payload.
     * @param array $rules The rules.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public static function make(array $data = [], array $rules = [])
    {
        return new static($data, $rules);
    }
    /**
     * Run sanitizer
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function run_sanitizer()
    {
        foreach ($this->rules as $key => $rule) {
            $key_segments = \explode('.', $key);
            $this->traverse_and_sanitize($this->data, $key_segments, [], $rule);
        }
    }
    /**
     * Recursively traverses the input data structure according to a dot-notated rule key,
     * handling wildcard segments (e.g., *) to apply sanitization rules at dynamic levels.
     *
     * @param mixed $current_data The current level of data being inspected. This is a portion
     * @param array $key_segments The remaining parts (split by '.') of the rule key that
     * @param array $traversed_path_stack The stack of key_segments already traversed so far. This is used
     * @param string $rule A sanitization rule to apply on the current data.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function traverse_and_sanitize($current_data, $key_segments, $traversed_path_stack, $rule)
    {
        // when all segments have been traversed
        if (empty($key_segments)) {
            $this->set_sanitized_data($traversed_path_stack, static::apply_rule($current_data, $rule, $this->data));
            return;
        }
        $segment = \array_shift($key_segments);
        if ($segment === '*') {
            if (!\is_array($current_data)) {
                $this->set_sanitized_data($traversed_path_stack, static::apply_rule($current_data, static::ARRAY, $this->data));
                return;
            }
            foreach ($current_data as $index => $item) {
                $this->traverse_and_sanitize($item, $key_segments, \array_merge($traversed_path_stack, [$index]), $rule);
            }
        } elseif (\is_array($current_data) && \array_key_exists($segment, $current_data)) {
            $this->traverse_and_sanitize($current_data[$segment], $key_segments, \array_merge($traversed_path_stack, [$segment]), $rule);
        }
    }
    /**
     * Set the sanitized data using a series of keys to traverse the nested array structure.
     *
     * @param array $keys An array of keys representing the path in the nested array structure.
     * @param mixed $value The value to set at the specified path in the sanitized data array.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function set_sanitized_data($keys, $value)
    {
        $ref =& $this->sanitized_data;
        foreach ($keys as $key) {
            if (!isset($ref[$key])) {
                $ref[$key] = [];
            }
            $ref =& $ref[$key];
        }
        $ref = $value;
    }
    /**
     * Apply sanitization.
     *
     * @param mixed $value The value.
     * @param mixed $type The type.
     * @param array $data The data payload.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public static function apply_rule($value, $type, array $data = [])
    {
        // For the null values, we don't need to sanitize them.
        if (\is_null($value)) {
            return null;
        }
        if ($type === null) {
            return $value;
        }
        switch ($type) {
            case static::ANY:
                return $value;
            case static::TRIM:
                $value = \trim($value);
                break;
            case static::TEXT:
                $value = sanitize_text_field($value);
                break;
            case static::RICH_TEXT:
                $value = wp_kses_post($value);
                break;
            case static::TEXTAREA:
                $value = sanitize_textarea_field($value);
                break;
            case static::EMAIL:
                $value = sanitize_email($value);
                break;
            case static::USERNAME:
                $value = sanitize_user($value);
                break;
            case static::URL:
                $value = sanitize_url($value);
                break;
            case static::KEY:
                $value = sanitize_key($value);
                break;
            case static::TITLE:
                $value = sanitize_title($value);
                break;
            case static::FILE_NAME:
                $value = sanitize_file_name($value);
                break;
            case static::MIME_TYPE:
                $value = sanitize_mime_type($value);
                break;
            case static::INT:
                $value = (int) $value;
                break;
            case static::FLOAT:
            case static::DOUBLE:
                $value = (float) $value;
                break;
            case static::BOOL:
                $value = rest_sanitize_boolean($value);
                break;
            case static::ARRAY:
                if (\is_array($value)) {
                    break;
                }
                if (empty($value)) {
                    return null;
                }
                if (is_valid_json($value)) {
                    $value = \json_decode($value, \true);
                    break;
                }
                return null;
            case static::DATE:
                if (!Date::is_valid_date($value)) {
                    return null;
                }
                $value = Date::start_of_day(Date::parse($value));
                break;
            case static::DATETIME:
                if (!Date::is_valid_date($value)) {
                    return null;
                }
                $value = Date::parse($value);
                break;
            case static::SERIALIZED:
                $value = maybe_serialize($value);
                break;
            case static::UNSERIALIZED:
                if (!is_serialized($value)) {
                    break;
                }
                $value = \unserialize(\trim($value), ['allowed_classes' => \false]);
                break;
            default:
                if (\is_callable($type)) {
                    $value = $type($value, $data);
                }
                break;
        }
        return $value;
    }
}
