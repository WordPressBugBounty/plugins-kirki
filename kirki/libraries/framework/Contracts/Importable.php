<?php

/**
 * Interface for objects that consume data from an external source.
 * The import method performs the ingestion logic and may throw on invalid input.
 * Mirror of Exportable for bidirectional data transfer patterns.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Importable
{
    /**
     * Import data from an external source.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function import();
}
