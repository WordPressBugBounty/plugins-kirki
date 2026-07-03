<?php

namespace Kirki\App\Models;

use Kirki\App\Constants\PostStatus;
use Kirki\App\Constants\PostTypes;
use Kirki\App\Traits\HasWordPressPostBehavior;
use Kirki\Framework\Database\Query\Model;
use function Kirki\Framework\with_prefix;

class Collection extends Model
{
    use HasWordPressPostBehavior;
    protected $table = 'posts';
    protected $primary_key = 'ID';

    protected $casts = [
        'ID' => 'integer',
    ];

    public static function query()
    {
        return parent::query()
            ->where('post_type', PostTypes::CONTENT_MANAGER)
            ->where_in('post_status', [
                PostStatus::DRAFT,
                PostStatus::PUBLISH,
                PostStatus::FUTURE,
            ]);
    }

    public function meta()
    {
        return $this->has_many(PostMeta::class, 'post_id', 'ID');
    }

    public function fields()
    {
        return $this->has_one(PostMeta::class, 'post_id', 'ID')->where('meta_key', with_prefix('cm_fields'));
    }

    public function basic_fields()
    {
        return $this->has_one(PostMeta::class, 'post_id', 'ID')->where('meta_key', with_prefix('cm_basic_fields'));
    }

    public function items()
    {
        return $this->has_many(CollectionItem::class, 'post_parent', 'ID')->where('post_type', 'like', PostTypes::CONTENT_MANAGER . '_%');
    }
}
