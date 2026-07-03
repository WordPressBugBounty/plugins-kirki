<?php

namespace Kirki\App\Resources\Media;

use Kirki\App\Constants\PostStatus;
use function Kirki\Framework\config;

defined('ABSPATH') || exit;

use Kirki\Framework\Resource;

class MediaResource extends Resource
{
    /**
     * Convert the media resource to an array.
     *
     * @return array The media data as an associative array.
     */
    public function to_array()
    {
        $categories = config('media.supports', []);
        $category = '';

        foreach ($categories as $type => $mime_types) {
            if (in_array($this->post_mime_type, $mime_types, true)) {
                $category = $type === 'svg' ? 'image' : $type;
                break;
            }
        }

        $meta = $this->mapped_meta();

        $alt = $meta['_wp_attachment_image_alt'] ?? '';
        $file_path = $meta['_wp_attached_file'] ?? '';
        $file_meta = $meta['_wp_attachment_metadata'] ?? '';
        $file_size = $file_meta['filesize'] ?? 0;
        $file_extension = $file_path ? pathinfo($file_path, PATHINFO_EXTENSION) : '';

        return [
            'id' => (int) $this->ID,
            'name' => $this->post_name,
            'alt' => $alt ? $alt : $this->post_title,
            'type' => $this->post_mime_type,
            'url' => $this->guid,
            'thumbnail' => wp_get_attachment_image_url($this->ID),
            'category' => $category,
            'trash' => PostStatus::TRASH === $this->post_status,
            'file_size' => size_format($file_size),
            'file_extension' => $file_extension,
        ];
    }

    /**
     * Map the item's meta as meta_key => meta_value.
     *
     * @return array<string, mixed>
     */
    protected function mapped_meta()
    {
        $map = [];

        foreach ($this->meta ?? [] as $meta) {
            $map[$meta->meta_key] = $meta->meta_value;
        }

        return $map;
    }
}
