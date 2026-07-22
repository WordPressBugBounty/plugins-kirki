<?php

namespace Kirki\App\Casts;

use Kirki\Framework\Database\Contracts\CastsAttributes;
use Kirki\Framework\Database\Query\Model;

defined('ABSPATH') || exit;


class AsSerialize implements CastsAttributes
{
     /**
      * Cast the given value.
      *
      * @param  array<string, mixed>  $attributes
      *
      * @return mixed
      */
     public function get(Model $model, string $key, $value, array $attributes)
     {
          return $value !== '' ? maybe_unserialize($value) : null;
     }

     /**
      * Prepare the given value for storage.
      *
      * @param  array<string, mixed>  $attributes
      *
      * @return string
      */
     public function set(Model $model, string $key, $value, array $attributes)
     {
          return $value ? maybe_serialize($value) : '';
     }
}