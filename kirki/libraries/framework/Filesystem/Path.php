<?php

/**
 * Static utilities for joining path segments and normalizing filesystem paths.
 * Handles trailing slashes, parent directory traversal, and cross-platform separators.
 * Used by Application and generators for consistent path construction.
 *
 * @package    Framework
 * @subpackage Filesystem
 * @since      1.0.0
 */
namespace Kirki\Framework\Filesystem;

\defined('ABSPATH') || exit;
class Path
{
    /**
     * Join path segments onto a base path.
     *
     * @param mixed $base The base.
     * @param mixed $paths The paths.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function join($base, ...$paths)
    {
        foreach ($paths as $index => $path) {
            if (empty($path) && $path !== '0') {
                unset($paths[$index]);
            } else {
                $paths[$index] = \DIRECTORY_SEPARATOR . \ltrim($path, \DIRECTORY_SEPARATOR);
            }
        }
        return $base . \implode('', $paths);
    }
    /**
     * Normalize a filesystem path without requiring it to exist.
     *
     * @param mixed $path The path.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function normalize($path)
    {
        $path = \str_replace('\\', '/', $path);
        $is_absolute = $path !== '' && $path[0] === '/';
        $parts = [];
        foreach (\explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                if (!empty($parts)) {
                    \array_pop($parts);
                }
                continue;
            }
            $parts[] = $part;
        }
        $normalized = \implode('/', $parts);
        if ($is_absolute) {
            return '/' . $normalized;
        }
        return $normalized;
    }
}
