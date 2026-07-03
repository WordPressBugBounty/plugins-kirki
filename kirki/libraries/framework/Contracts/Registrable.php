<?php

/**
 * Contract for registering something like post type, taxonomies etc.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Registrable
{
    /**
     * Register something.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function register();
}
