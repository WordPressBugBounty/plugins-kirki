<?php

namespace Kirki\App\Models;

defined('ABSPATH') || exit;

use Kirki\App\Casts\AsSerialize;
use Kirki\Framework\Database\Query\Model;

class PostMeta extends Model
{
    protected $table = 'postmeta';

    protected $primary_key = 'meta_id';
    protected $timestamps = false;

    protected $fillable = [
        'post_id',
        'meta_key',
        'meta_value',
    ];

    protected $casts = [
        'post_id' => 'integer',
        'meta_value' => AsSerialize::class,
    ];

    /**
     * @param int $post_id
     * @param string $meta_key
     * @param mixed $default
     * 
     * @return mixed
     */
    public static function get_meta_value(int $post_id, string $meta_key, $default = null)
    {
        $meta = static::where('post_id', $post_id)->where('meta_key', $meta_key)->first();
        return $meta && $meta->meta_value !== null && $meta->meta_value !== '' ? $meta->meta_value : $default;
    }

    /**
     * @param int $post_id
     * @param string $meta_key
     * @param mixed $meta_value
     * 
     * @return static
     */
    public static function update_meta_value(int $post_id, string $meta_key, $meta_value)
    {
        return static::update_or_create(['post_id' => $post_id, 'meta_key' => $meta_key], ['meta_value' => $meta_value]);
    }

    public function post()
    {
        return $this->belongs_to(Post::class, 'post_id', 'ID');
    }
}
