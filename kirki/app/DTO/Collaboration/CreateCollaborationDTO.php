<?php

namespace Kirki\App\DTO\Collaboration;

use Kirki\App\Constants\CollaborationStatus;
use Kirki\Framework\DTO;

defined('ABSPATH') || exit;

class CreateCollaborationDTO extends DTO {
    /** @var string */
    public $session_id = '';

    /** @var string */
    public $parent = '';

    /** @var int */
    public $parent_id = 0;

    /** @var array */
    public $data = [];

    /** @var int */
    public $status = CollaborationStatus::ACTIVE;
}