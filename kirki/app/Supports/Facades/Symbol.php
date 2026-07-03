<?php

namespace Kirki\App\Supports\Facades;

defined( 'ABSPATH' ) || exit;

use Kirki\App\DTO\Symbol\SymbolDTO;
use Kirki\App\Managers\SymbolManager;
use Kirki\Framework\Facade;

/**
 * Symbol Support Facade
 * @method static SymbolDTO|null save(SymbolDTO $symbol)
 * @method static string get_preview_html(SymbolDTO $symbol, $options = [], $variable_css = true)
 * 
 * @see \Kirki\App\Managers\SymbolManager
 */
class Symbol extends Facade
{
    public static function get_accessor()
    {
        return SymbolManager::class;
    }
}
