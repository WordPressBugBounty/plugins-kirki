<?php

namespace Kirki\App\Managers;

defined('ABSPATH') || exit;

use Kirki\App\Constants\PostTypes;
use Kirki\App\DTO\Symbol\SymbolDTO;
use Kirki\App\Models\Post as PostModel;
use Kirki\App\Supports\Facades\Page;
use Kirki\HelperFunctions;

use function Kirki\App\is_truthy;
use function Kirki\App\to_boolean;

class SymbolManager
{
    /**
     * Save symbol to database
     * 
     * @param SymbolDTO $symbol
     * 
     * @return SymbolDTO|null
     */
    public function save(SymbolDTO $symbol)
    {
        if (!is_truthy($symbol->symbolData)) {
            return null;
        }

        if (is_truthy($symbol->id)) {
            $symbol->id = null;
        }

        $post = PostModel::create_post([
            'post_type' => PostTypes::SYMBOL,
        ]);

        $symbol->symbolData['data'][$symbol->symbolData['root']]['properties']['symbolId'] = $post->ID;

        Page::save_blocks($post->ID, $symbol->symbolData);
        Page::save_editor_mode($post->ID);

        $symbol->id = $post->ID;
        $symbol->type = isset($symbol->symbolData['category']) ? $symbol->symbolData['category'] : 'other';

        return $symbol;
    }

    public function get_preview_html(SymbolDTO $symbol, $options = [], $variable_css = true) {
		if (!is_truthy($symbol->symbolData)) {
			return '';
		}

		$params = [
			'blocks'                 => $symbol->symbolData['data'],
			'style_blocks'           => $symbol->symbolData['styleBlocks'],
			'root'                   => $symbol->symbolData['root'],
			'post_id'                => $symbol->id,
			'options'                => $options,
			'get_style'              => true,
			'get_variable'           => to_boolean($variable_css),
			'should_take_app_script' => false,
			'prefix'                 => 'kirki-s' . $symbol->id,
		];

        // @todo: Need to refactor HelperFunctions::get_html_using_preview_script($params) first
        $html = HelperFunctions::get_html_using_preview_script($params);

		return $html;
	}
}