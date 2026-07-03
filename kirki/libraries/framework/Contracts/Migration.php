<?php

/**
 * Interface Migration Defines the contract for database migrations.
 * Each implementing class should provide logic for setting up (up) and tearing down (down) database structures.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Migration
{
    /**
     * Run the migration.
     *
     * This method should contain logic to create or modify database tables.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function up();
    /**
     * Reverse the migration.
     *
     * This method should contain logic to undo changes made by the `up()` method.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function down();
}
