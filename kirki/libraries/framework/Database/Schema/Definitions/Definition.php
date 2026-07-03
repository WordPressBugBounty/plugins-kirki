<?php

/**
 * Represents a single database column definition with fluent attribute configuration.
 * Supports type selection, length, nullability, defaults, and indexing through chainable methods.
 * Feeds column metadata into the schema structure and compiler pipeline.
 *
 * @package    Framework
 * @subpackage Database\Schema\Definitions
 * @since      1.0.0
 */
namespace Kirki\Framework\Database\Schema\Definitions;

\defined('ABSPATH') || exit;
use Kirki\Framework\Supports\Flex;
/**
 * Define the definition of the database schema columns.
 *
 * @method $this comment(string $comment)
 * @method $this default($value)
 * @method $this nullable()
 * @method $this auto_increment()
 * @method $this unsigned()
 * @method $this precision(int $precision)
 * @method $this total(int $total)
 * @method $this places(int $places)
 * @method $this length(int $length)
 * @method $this primary()
 * @method $this unique()
 * @method $this use_current()
 */
class Definition extends Flex
{
    // Column definition
}
