<?php

namespace Kirki\App\Models;

use Kirki\App\Constants\PostStatus;
use Kirki\App\Constants\PostTypes;

defined('ABSPATH') || exit;

use Kirki\App\Traits\SearchablePost;
use Kirki\Framework\Database\Query\Model;
class Media extends Model
{
    use SearchablePost;

    protected $table = 'posts';

    protected $primary_key = 'ID';

    protected $timestamps = false;

    public static function query()
    {
        return parent::query()
            ->where('post_type', PostTypes::WP_ATTACHMENT)
            ->where_not('post_status', PostStatus::TRASH);
    }

    public function meta()
    {
        return $this->has_many(PostMeta::class, 'post_id', 'ID');
    }
}
