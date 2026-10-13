<?php

namespace Kirki\App\Casts;

use Kirki\Framework\Database\Contracts\CastsAttributes;
use Kirki\Framework\Database\Query\Model;

defined('ABSPATH') || exit;

/**
 * Saves a value the same way update_post_meta() does, so moved data stays exactly the same.
 * Unlike AsSerialize, it keeps empty values, e.g. an empty array stays `a:0:{}`.
 */
class AsMetaValue implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @return mixed
     */
    public function get(Model $model, string $key, $value, array $attributes)
    {
        return $value === null ? null : maybe_unserialize($value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @return string
     */
    public function set(Model $model, string $key, $value, array $attributes)
    {
        return $value === null ? '' : maybe_serialize($value);
    }
}
