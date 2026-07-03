<?php

/**
 * Interface for discovery classes that persist their scan results to a cache file.
 * Implementors define how cached data is written and where it lives on disk.
 * Enables production-mode skipping of filesystem scans for listeners and policies.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Cacheable
{
    /**
     * Get the cache key.
     *
     * @param ?string $path The path.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function cache(?string $path = null);
}
