<?php

/**
 * Shared URI segment parser for REST and site routes.
 * Supports {name} and {name:type} syntax plus where() pattern overrides.
 *
 * @package    Framework
 * @subpackage Routing
 * @since      1.0.0
 */
namespace Kirki\Framework\Routing;

\defined('ABSPATH') || exit;
use Kirki\Framework\Sanitizer;
class RouteParser
{
    /**
     * Predefined param types mapped to regex fragments without capturing parens.
     *
     * @var array<string, string>
     *
     * @since 1.0.0
     */
    protected const TYPES = ['any' => '[^/]+', 'int' => '\\d+', 'alpha' => '[a-zA-Z]+', 'alnum' => '[a-zA-Z0-9]+', 'slug' => '[a-zA-Z0-9-]+'];
    /**
     * Sanitizer callbacks keyed by param type.
     *
     * @var array<string, string>
     *
     * @since 1.0.0
     */
    protected const SANITIZERS = ['int' => Sanitizer::INT, 'alpha' => Sanitizer::TEXT, 'alnum' => Sanitizer::TEXT, 'slug' => Sanitizer::TITLE, 'any' => Sanitizer::TEXT];
    /**
     * Parse a Laravel-style URI into an ordered list of segments.
     *
     * @param string $uri Route URI such as products/{id:int}.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function parse_segments(string $uri)
    {
        $parts = \explode('/', \trim($uri, '/'));
        $segments = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (\preg_match('/^\\{(\\w+)(?::(.+))?\\}$/', $part, $matches)) {
                $segments[] = ['type' => 'param', 'name' => $matches[1], 'inline_type' => $matches[2] ?? null];
                continue;
            }
            $segments[] = ['type' => 'literal', 'value' => $part];
        }
        return $segments;
    }
    /**
     * Extract inline param types from parsed segments.
     *
     * @param array $segments Parsed segments.
     *
     * @return array<string, string>
     *
     * @since 1.0.0
     */
    public function extract_param_types(array $segments)
    {
        $param_types = [];
        foreach ($segments as $segment) {
            if ($segment['type'] !== 'param' || $segment['inline_type'] === null) {
                continue;
            }
            $param_types[$segment['name']] = $segment['inline_type'];
        }
        return $param_types;
    }
    /**
     * Resolve a type keyword or raw regex to a regex fragment.
     *
     * @param string $type Type keyword or raw regex.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function resolve_regex(string $type)
    {
        return static::TYPES[$type] ?? $type;
    }
    /**
     * Resolve a sanitizer rule for a param type.
     *
     * @param string $type Type keyword.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function resolve_sanitizer(string $type)
    {
        return static::SANITIZERS[$type] ?? Sanitizer::TEXT;
    }
    /**
     * Build a REST-compatible endpoint pattern with named capture groups.
     *
     * @param string $endpoint The route endpoint.
     * @param array $patterns Param name to regex overrides from where().
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function format_rest_endpoint(string $endpoint, array $patterns = [])
    {
        $segments = $this->parse_segments($endpoint);
        $inline_types = $this->extract_param_types($segments);
        $compiled = [];
        foreach ($segments as $segment) {
            if ($segment['type'] === 'literal') {
                $compiled[] = $segment['value'];
                continue;
            }
            $name = $segment['name'];
            $type = $patterns[$name] ?? $inline_types[$name] ?? 'any';
            $regex = $this->resolve_regex($type);
            $compiled[] = '(?P<' . $name . '>' . $regex . ')';
        }
        return \implode('/', $compiled);
    }
    /**
     * Build a site-route rewrite matching pattern and ordered param names.
     *
     * @param array $segments Parsed segments.
     * @param array $param_types Param name to type/regex map.
     *
     * @return array{0:string,1:string[]}
     *
     * @since 1.0.0
     */
    public function build_site_pattern(array $segments, array $param_types = [])
    {
        $compiled = [];
        $param_names = [];
        foreach ($segments as $segment) {
            if ($segment['type'] === 'literal') {
                $compiled[] = \preg_quote($segment['value'], '#');
                continue;
            }
            $name = $segment['name'];
            $type = $param_types[$name] ?? 'any';
            $param_names[] = $name;
            $compiled[] = '(' . $this->resolve_regex($type) . ')';
        }
        $pattern = '^' . \implode('/', $compiled) . '/?$';
        return [$pattern, $param_names];
    }
    /**
     * Normalize a URI by stripping leading/trailing slashes.
     *
     * @param string $uri The URI.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function normalize_uri(string $uri)
    {
        return \trim($uri, '/');
    }
}
