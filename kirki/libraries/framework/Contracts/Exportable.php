<?php

/**
 * Interface for objects that can serialize themselves into a DTO for external output.
 * The export method produces a structured transfer object ready for APIs or files.
 * Supports import/export workflows between services.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Exportable
{
    /**
     * Export data as an DTO instance.
     *
     * @return \Framework\DTO|mixed
     *
     * @since 1.0.0
     */
    public function export();
}
