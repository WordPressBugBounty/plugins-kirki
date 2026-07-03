<?php

/**
 * Orchestrates running and rolling back migration classes.
 * Compares registered migrations against the repository history and invokes up/down methods.
 * Validates each migration implements the Migration contract before execution.
 *
 * @package    Framework
 * @subpackage Database\Migrations
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Migrations;

\defined('ABSPATH') || exit;
use Kirki\Framework\Contracts\Migration;
use Kirki\Framework\Supports\Facades\Schema;
use Exception;
class Migrator
{
    /**
     * The migration repository instance.
     *
     * @var MigrationRepository|null
     *
     * @since 1.0.0
     */
    protected $repository = null;
    /**
     * Create a new migrator instance.
     *
     * @param MigrationRepository $repository The repository.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct(MigrationRepository $repository)
    {
        $this->repository = $repository;
    }
    /**
     * Run the migrations.
     *
     * @return void
     *
     * @throws \Exception
     *
     * @since 1.0.0
     */
    public function run()
    {
        $migrations = $this->repository->get_registered_migrations();
        $previous_migrations = $this->repository->get_previous_migrations();
        if (empty($migrations)) {
            return;
        }
        foreach ($migrations as $migration) {
            $class_name = \get_class($migration);
            if (!\class_exists($class_name)) {
                throw new Exception(\sprintf('Class [%s] does not exist', $class_name));
            }
            if (!$migration instanceof Migration) {
                throw new Exception(\sprintf('Class [%s] must implements [%s]', $class_name, Migration::class));
            }
            if (!empty($previous_migrations[$class_name])) {
                continue;
            }
            $migration->up();
            $previous_migrations[$class_name] = \true;
        }
        // $this->repository->update_migrations($previous_migrations);
    }
    /**
     * Rollback the migrations.
     *
     * @return void
     *
     * @throws \Exception
     *
     * @since 1.0.0
     */
    public function rollback()
    {
        if (!$this->repository->is_rollback_enabled()) {
            return;
        }
        $migrations = $this->repository->get_registered_migrations();
        $previous_migrations = $this->repository->get_previous_migrations();
        if (empty($migrations)) {
            return;
        }
        foreach ($migrations as $migration) {
            $class_name = \get_class($migration);
            if (empty($previous_migrations[$class_name])) {
                continue;
            }
            if (!\class_exists($class_name)) {
                throw new Exception(\sprintf('Class [%s] does not exist', $class_name));
            }
            if (!$migration instanceof Migration) {
                throw new Exception(\sprintf('Class [%s] must implements [%s]', $class_name, Migration::class));
            }
            $migration->down();
            unset($previous_migrations[$class_name]);
        }
        if (empty($previous_migrations)) {
            $this->repository->remove_migrations();
            return;
        }
        $this->repository->update_migrations($previous_migrations);
    }
    /**
     * Refresh the database schema.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function fresh()
    {
        try {
            $migrations = $this->repository->get_registered_migrations();
            Schema::disabled_checking_foreign_key_constraints();
            foreach ($migrations as $migration) {
                $migration->down();
            }
        } finally {
            Schema::enabled_checking_foreign_key_constraints();
        }
    }
}
