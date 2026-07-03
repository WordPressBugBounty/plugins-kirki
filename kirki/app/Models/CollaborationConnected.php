<?php

namespace Kirki\App\Models;

defined('ABSPATH') || exit;

use Kirki\Framework\Database\Query\Model;

class CollaborationConnected extends Model
{
    protected $table = 'kirki_collaborations_connected';
    
    protected $primary_key = 'id';
}
