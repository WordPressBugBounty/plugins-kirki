<?php

/**
 * Eloquent model mapping to the WordPress wp_options table.
 * Disables timestamps and casts option_value through unserialize for PHP-serialized storage.
 * Used by OptionManager for query-based option access beyond get_option.
 *
 * @package    Framework
 * @subpackage Wordpress\Models
 * @since      1.0.0
 */
namespace Kirki\Framework\Wordpress\Models;

\defined('ABSPATH') || exit;
use Kirki\Framework\Database\Query\Model;
class Option extends Model
{
    /**
     * The timestamps.
     *
     * @var bool
     *
     * @since 1.0.0
     */
    protected $timestamps = \false;
    /**
     * The table.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $table = 'options';
    /**
     * The primary key.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $primary_key = 'option_id';
    /**
     * The casts.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $casts = ['option_id' => 'integer', 'option_name' => 'string', 'option_value' => 'unserialize'];
    /**
     * The fillable.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $fillable = ['option_name', 'option_value', 'autoload'];
}
