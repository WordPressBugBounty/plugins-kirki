<?php

/**
 * Executor contract.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Executor
{
    /**
     * Execute the executor.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function run();
}
