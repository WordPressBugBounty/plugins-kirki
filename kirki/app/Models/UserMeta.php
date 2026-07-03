<?php

namespace Kirki\App\Models;

defined('ABSPATH') || exit;

use Kirki\App\Casts\AsSerialize;
use Kirki\Framework\Database\Query\Model;

class UserMeta extends Model
{
    protected $table = 'usermeta';

    protected $primary_key = 'umeta_id';

    protected $timestamps = false;

    protected $casts = [
        'user_id' => 'integer',
        'meta_key' => AsSerialize::class,
    ];

    protected $fillable = [
        'user_id',
        'meta_key',
        'meta_value',
    ];
}
