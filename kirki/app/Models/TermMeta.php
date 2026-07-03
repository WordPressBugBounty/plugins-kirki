<?php

namespace Kirki\App\Models;

use Kirki\App\Casts\AsSerialize;
use Kirki\Framework\Database\Query\Model;

class TermMeta extends Model
{
    protected $table = 'termmeta';

    protected $primary_key = 'meta_id';

    protected $timestamps = false;

    protected $casts = [
        'term_id' => 'integer',
        'meta_value' => AsSerialize::class,
    ];

    protected $fillable = [
        'term_id',
        'meta_key',
        'meta_value',
    ];
}
