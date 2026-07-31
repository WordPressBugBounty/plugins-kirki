<?php

/**
 * MimeTypes class for handling file's mimetypes.
 *
 * @package    Framework
 * @subpackage Filesystem
 * @since      1.0.0
 */
namespace Kirki\Framework\Filesystem;

use finfo;
use Kirki\Framework\Supports\Arr;
\defined('ABSPATH') || exit;
class MimeTypes
{
    /**
     * The allowed mimetypes.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $allowed_mime_types = [];
    /**
     * The allowed extensions.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $allowed_extensions = [];
    /**
     * The extensions mime types map.
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $extensions_mime_types = [];
    /**
     * The MimeTypes instance.
     *
     * @var MimeTypes|null
     *
     * @since 1.0.0
     */
    protected static $instance = null;
    /**
     * The images mimetypes.
     *
     * @var array
     *
     * @since 1.0.0
     */
    public array $images_mimetypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    /**
     * Construct the MimeTypes instance.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function __construct()
    {
        $this->allowed_mime_types = \array_values(get_allowed_mime_types());
        $this->allowed_extensions = Arr::flatten(Arr::map(\array_keys(get_allowed_mime_types()), fn($extension) => \explode('|', $extension)));
        $this->extensions_mime_types = $this->prepare_extensions_mime_types_map();
    }
    /**
     * Make the MimeTypes instance.
     *
     * @return MimeTypes The MimeTypes instance.
     *
     * @since 1.0.0
     */
    public static function make()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    /**
     * Prepare the allowed mimetypes.
     *
     * @return array The allowed mimetypes.
     *
     * @since 1.0.0
     */
    protected function prepare_allowed_mime_types()
    {
        return \array_values(get_allowed_mime_types());
    }
    /**
     * Prepare the allowed extensions.
     *
     * @return array The allowed extensions.
     *
     * @since 1.0.0
     */
    protected function prepare_allowed_extensions()
    {
        return Arr::flatten(Arr::map(\array_keys(get_allowed_mime_types()), fn($extension) => \explode('|', $extension)));
    }
    /**
     * Prepare the extensions mime types map.
     *
     * @return array The extensions mime types map.
     *
     * @since 1.0.0
     */
    protected function prepare_extensions_mime_types_map()
    {
        $allowed_mime_types = get_allowed_mime_types();
        $mapping = [];
        foreach ($allowed_mime_types as $key => $mime_type) {
            $extensions = \explode('|', $key);
            foreach ($extensions as $extension) {
                $mapping[$mime_type] = \array_merge($mapping[$mime_type] ?? [], [$extension]);
            }
        }
        return $mapping;
    }
    /**
     * Check if the mimetypes are allowed.
     *
     * @param array $mimetypes The mimetypes.
     *
     * @return array The allowed mimetypes.
     *
     * @since 1.0.0
     */
    public function allowed_mimetypes(array $mimetypes)
    {
        return \array_values(\array_intersect($this->allowed_mime_types, $mimetypes));
    }
    /**
     * Check if the extensions are allowed.
     *
     * @param array $extensions The extensions.
     *
     * @return array The allowed extensions.
     *
     * @since 1.0.0
     */
    public function allowed_extensions(array $extensions)
    {
        return \array_values(\array_intersect($this->allowed_extensions, $extensions));
    }
    /**
     * Get the mimetype of the file.
     *
     * @param File $file The file.
     *
     * @return string The mimetype.
     *
     * @since 1.0.0
     */
    public function get_mimetype(File $file)
    {
        $finfo = new finfo(\FILEINFO_MIME_TYPE);
        return \strtolower($finfo->file($file->getRealPath()));
    }
    /**
     * Guess the extension of the file.
     *
     * @param File $file The file.
     *
     * @return array<string> The extension.
     *
     * @since 1.0.0
     */
    public function guess_extensions(File $file)
    {
        $mimetype = $this->get_mimetype($file);
        return $this->extensions_mime_types[$mimetype] ?? [];
    }
}
