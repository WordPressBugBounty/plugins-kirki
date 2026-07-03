<?php

namespace Kirki\App\Models;

defined('ABSPATH') || exit;

use Kirki\Framework\Database\Query\Model;

class CollaborationSent extends Model
{
    protected $table = 'kirki_collaborations_sent';
    
    protected $primary_key = 'id';
}
