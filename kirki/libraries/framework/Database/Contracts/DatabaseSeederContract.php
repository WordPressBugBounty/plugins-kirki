<?php

/**
 * Database Seeder Contract
 *
 * @package    Framework
 * @subpackage Database
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Contracts;

\defined('ABSPATH') || exit;
interface DatabaseSeederContract
{
    /**
     * Run the database seeders.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function run();
}
