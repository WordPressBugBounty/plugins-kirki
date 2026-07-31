<?php 

namespace Kirki\App\DTO\Page;

defined('ABSPATH') || exit;

use Kirki\App\Constants\PostStatus;
use Kirki\App\Constants\PostTypes;
use Kirki\Framework\DTO;

class PageFilterDTO extends DTO
{
    /** @var string search param */
    public $query;

    /** @var int page number */
    public $current_page = 1;

    /** @var int number of posts per page */
    public $limit = 20;

    /** @var string[] post types */
    public $post_types = [PostTypes::WP_PAGE];

    /** @var int[] exclude page ids */
    public $exclude_page_ids = [];

    /** @var string[] */
    public $post_statuses = [
        PostStatus::PUBLISH,
        PostStatus::DRAFT
    ];
}