<?php

namespace Kirki\App\Constants;

use Kirki\Framework\Concerns\HasConstants;

defined('ABSPATH') || exit;

class CollaborationStatus 
{
    use HasConstants;
    
    const ACTIVE = 1;
    const SENT = 2;
    const EXPIRED = 0;
}