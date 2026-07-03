<?php

namespace Kirki\App\Models;

use Kirki\App\Constants\PostStatus;
use Kirki\App\Constants\PostTypes;
use Kirki\App\Traits\HasWordPressPostBehavior;
use Kirki\App\Traits\SearchablePost;
use Kirki\Framework\Database\Query\Model;

use function Kirki\Framework\with_prefix;

class CollectionItem extends Model
{
    use HasWordPressPostBehavior, SearchablePost;

    protected $table = 'posts';
    protected $primary_key = 'ID';

    protected $casts = [
        'ID' => 'integer',
    ];

    public static function query()
    {
        return parent::query()
            ->where('post_type', 'like', PostTypes::CONTENT_MANAGER . '_%')
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

    public function collection()
    {
        return $this->belongs_to(Collection::class, 'post_parent', 'ID');
    }

    public function references()
    {
        return $this->has_many(Reference::class, 'post_id', 'ID');
    }

    /**
     * The item's non-reference field values, stored as post meta
     * (`kirki_cm_field_{parent}_{field_id}`).
     */
    public function fields()
    {
        return $this->has_many(PostMeta::class, 'post_id', 'ID')
            ->where('meta_key', 'like', with_prefix('cm_field_') . '%');
    }
}
