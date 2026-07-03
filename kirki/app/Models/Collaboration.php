<?php

namespace Kirki\App\Models;

defined('ABSPATH') || exit;

use Kirki\Framework\Database\Query\Model;

class Collaboration extends Model
{
    protected $table = 'kirki_collaborations';

    protected $primary_key = 'id';

    protected $fillable = [
        'user_id',
        'session_id',
        'parent',
        'parent_id',
        'data',
        'status',
    ];
}
