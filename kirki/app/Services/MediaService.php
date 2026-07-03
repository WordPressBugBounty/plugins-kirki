<?php

namespace Kirki\App\Services;

use Kirki\App\Constants\PostStatus;
use Kirki\App\Constants\PostTypes;
use function Kirki\Framework\config;

defined('ABSPATH') || exit;

use Kirki\App\DTO\Media\MediaListFilterDTO;
use Kirki\App\Models\Media;

class MediaService
{
	/**
	 * Paginate media items.
	 *
	 * @param MediaListFilterDTO $dto
	 * @return \Kirki\Framework\Database\Query\Paginator
	 */
	public function paginated(MediaListFilterDTO $dto)
	{
		$supported_media_types = config('media.supports');

		$query = Media::query();

		if (!empty($dto->category)) {
			$categories = explode(',', $dto->category);
			$mime_types = [];

			foreach ($categories as $category) {
				$category = trim($category);

				if (isset($supported_media_types[$category])) {
					$mime_types = array_merge($mime_types, $supported_media_types[$category]);
				}
			}

			if (!empty($mime_types)) {
				$query->where_in('post_mime_type', $mime_types);
			}
		}

		if (!empty($dto->search)) {
			$query->search($dto->search);
		}

		$query->order_by($dto->sort_by, $dto->sort_order);

		return $query->with('meta')->paginate($dto->limit, $dto->page);
	}
}