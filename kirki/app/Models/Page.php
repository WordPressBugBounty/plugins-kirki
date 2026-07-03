<?php

namespace Kirki\App\Models;

defined('ABSPATH') || exit;

use Exception;
use Kirki\App\Traits\HasWordPressPostBehavior;
use Kirki\App\Traits\SearchablePost;
use Kirki\Framework\Database\Query\Model;

class Page extends Model
{
    use SearchablePost, HasWordPressPostBehavior;

    protected $table = 'posts';

    protected $primary_key = 'ID';
    
    protected $timestamps = false;

    public function meta()
    {
        return $this->has_many(PostMeta::class, 'post_id', 'ID');
    }

    public function taxonomies()
    {
        return $this->belongs_to_many(TermTaxonomy::class, 'term_relationships', 'object_id', 'term_taxonomy_id');
    }

    public function categories()
    {
        return $this->taxonomies()->where('taxonomy', 'category');
    }

    public function tags()
    {
        return $this->taxonomies()->where('taxonomy', 'post_tag');
    }
}
