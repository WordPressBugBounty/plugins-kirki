<?php

/**
 * Defines a foreign key constraint with configurable ON UPDATE and ON DELETE actions.
 * Links local and referenced columns while expressing referential integrity rules in schema migrations.
 * Integrates with the structure builder when declaring relational table constraints.
 * Allows setting actions such as CASCADE, RESTRICT, SET NULL, and NO ACTION for both update and delete events.
 *
 * @package    Framework
 * @subpackage Database\Schema\Definitions
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Schema\Definitions;

\defined('ABSPATH') || exit;
/**
 * Define the foreign key constraint of the database schema.
 *
 * @method $this on(string $table)
 * @method $this on_update(string $action)
 * @method $this on_delete(string $action)
 * @method $this references(string $references)
 */
class ForeignKeyDefinition extends Definition
{
    /**
     * Set ON UPDATE action to CASCADE.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function cascade_on_update()
    {
        return $this->on_update('cascade');
    }
    /**
     * Set ON DELETE action to CASCADE.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function cascade_on_delete()
    {
        return $this->on_delete('cascade');
    }
    /**
     * Set ON UPDATE action to RESTRICT.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function restrict_on_update()
    {
        return $this->on_update('restrict');
    }
    /**
     * Set ON DELETE action to RESTRICT.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function restrict_on_delete()
    {
        return $this->on_delete('restrict');
    }
    /**
     * Set ON UPDATE action to SET NULL.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function null_on_update()
    {
        return $this->on_update('set null');
    }
    /**
     * Set ON DELETE action to SET NULL.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function null_on_delete()
    {
        return $this->on_delete('set null');
    }
    /**
     * Set ON UPDATE action to NO ACTION.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function no_action_on_update()
    {
        return $this->on_update('no action');
    }
    /**
     * Set ON DELETE action to NO ACTION.
     *
     * @return $this
     *
     * @since 1.0.0
     */
    public function no_action_on_delete()
    {
        return $this->on_delete('no action');
    }
}
