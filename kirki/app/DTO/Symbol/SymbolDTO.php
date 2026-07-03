<?php

namespace Kirki\App\DTO\Symbol;

defined('ABSPATH') || exit;

use Kirki\Framework\DTO;

class SymbolDTO extends DTO 
{
    /** @var int */
    public $id;

    /** @var array|null */
    public $symbolData;

    /** @var string */
    public $type = 'other';

    /** @var string */
    public $setAs = '';
}