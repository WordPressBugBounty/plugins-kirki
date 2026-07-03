<?php

namespace Kirki\App\Http\Controllers\Api;

use Exception;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Services\EditorService;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Http\Response;

use function Kirki\Framework\response;

defined('ABSPATH') || exit;

class EditorController
{
    /** @var EditorService */
    protected $service;

    public function __construct(EditorService $service)
    {
        $this->service = $service;
    }

    public function back_to_kirki_editor(Request $request)
    {
        $page = PageModel::find($request->int('postId'));

        if (empty($page)) {
            throw new Exception(esc_html__('Page not found.', 'kirki'), Response::NOT_FOUND);
        }

        $this->service->back_to_kirki_editor($page, $request->string('post_title'));

        return response()->json([
            'data' => [
                'status' => true
            ],
        ]);
    }

    public function back_to_wordpress_editor(Request $request)
    {
        $page = PageModel::find($request->int('postId'));

        if (empty($page)) {
            throw new Exception(esc_html__('Page not found.', 'kirki'), Response::NOT_FOUND);
        }

        $this->service->back_to_wordpress_editor($page);

        return response()->json([
            'data' => [
                'status' => true
            ],
        ]);
    }
}