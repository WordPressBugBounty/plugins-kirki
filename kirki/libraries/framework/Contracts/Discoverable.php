<?php

/**
 * Single-method contract for classes that scan the filesystem for registrable items.
 * The discover method performs the scan and populates internal state.
 * Implemented by ListenerDiscovery and PolicyDiscovery.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Discoverable
{
    /**
     * Discover from the file system.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function discover();
}
