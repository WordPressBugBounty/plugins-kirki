<?php

/**
 * Trait that retrieves nested array values using dot-notation keys.
 * Walks each segment recursively and returns a default when any level is missing or empty.
 * Shared by classes that need deep array access without pulling in a full Arr helper.
 *
 * @package    Framework
 * @subpackage Concerns
 * @since      1.0.0
 */
namespace Kirki\Framework\Concerns;

\defined('ABSPATH') || exit;
trait DeepGettable
{
    /**
     * Return the nested setting values by key recursively.
     *
     * @param array $settings The settings.
     * @param mixed $keys The keys.
     * @param mixed $default The default.
     *
     * @return mixed|null
     *
     * @since 1.0.0
     */
    protected function deep_get(array $settings, $keys, $default = null)
    {
        $keys = \is_array($keys) ? $keys : \explode('.', $keys);
        if (empty($keys) || empty($settings)) {
            return $default;
        }
        $parent_key = $keys[0];
        $child_keys = \array_slice($keys, 1);
        if (empty($child_keys)) {
            return $settings[$parent_key] ?? $default;
        }
        if (empty($settings[$parent_key])) {
            return $default;
        }
        return $this->deep_get($settings[$parent_key], $child_keys, $default);
    }
}
