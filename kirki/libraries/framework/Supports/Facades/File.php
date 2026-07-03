<?php

/**
 * Facade proxy for the Filesystem service with read, write, exists, and directory helpers.
 * Wraps WordPress filesystem operations behind a testable static API.
 * Registered by FileSystemServiceProvider.
 *
 * @package    Framework
 * @subpackage Supports\Facades
 * @since      1.0.0
 */
namespace Kirki\Framework\Supports\Facades;

\defined('ABSPATH') || exit;
use Kirki\Framework\Facade;
/**
 * Facade proxy for the Filesystem service with read, write, exists, and directory helpers.
 *
 * @method static bool is_file(string $file)
 * @method static array glob(string $pattern, int $flags = 0)
 * @method static string basename(string $path)
 * @method static string name(string $path)
 * @method static string extension(string $path)
 * @method static bool copy(string $path, string $target)
 * @method static bool move(string $path, string $target)
 * @method static bool delete(string|array $paths, bool $recursive = true)
 * @method static mixed chmod(string $path, int|null $mode = null)
 * @method static bool append(string $path, string $data)
 * @method static bool prepend(string $path, string $data)
 * @method static bool put(string $path, string $data)
 * @method static string get(string $path)
 * @method static array json(string $path, int $flags = 0)
 * @method static string hash(string $path, string $algorithm = 'md5')
 * @method static bool exists(string $path)
 * @method static bool missing(string $path)
 * @method static string dirname(string $path)
 * @method static string type(string $path)
 * @method static string mime_type(string $path)
 * @method static int|false size(string $path)
 * @method static bool is_directory(string $path)
 * @method static bool is_readable(string $path)
 * @method static bool is_writable(string $path)
 * @method static int|false last_modified(string $path)
 * @method static bool make(string $path)
 * @method static bool make_dir(string $path)
 * @see    \Framework\Filesystem\Filesystem
 */
class File extends Facade
{
    /**
     * Get the accessor.
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public static function get_accessor()
    {
        return 'files';
    }
}
