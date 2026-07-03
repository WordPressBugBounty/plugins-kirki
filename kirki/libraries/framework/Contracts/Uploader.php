<?php

/**
 * Contract for handling file uploads as WordPress media attachments.
 * Defines upload returning structured attachment metadata including sizes and mime info.
 * Abstracts media library integration behind a testable interface.
 *
 * @package    Framework
 * @subpackage Contracts
 * @since      1.0.0
 */
namespace Kirki\Framework\Contracts;

\defined('ABSPATH') || exit;
interface Uploader
{
    /**
     * File upload as attachemnt and get all attachment's info with meta data
     *
     * @param array $files The array of files from the $_FILES superglobal.
     * @param int $parent_post_id The parent post id.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function upload(array $files, int $parent_post_id = 0);
}
