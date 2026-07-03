<?php

namespace Kirki\App\Models;

defined('ABSPATH') || exit;

use Kirki\App\Models\UserMeta as UserMetaModel;
use Kirki\App\Traits\SearchableUser;
use Kirki\Framework\Database\Query\Model;

class User extends Model
{
    use SearchableUser;

    protected $table = 'users';

    protected $primary_key = 'ID';
    
    protected $timestamps = false;

    protected $casts = [
        'ID' => 'integer',
    ];

    public function meta()
    {
        return $this->has_many(UserMetaModel::class, 'user_id', 'ID');
    }
}
