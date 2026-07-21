<?php

namespace Kirki\App\Http\Controllers\Api;

defined('ABSPATH') || exit;

use Exception;
use Kirki\App\Constants\PostTypes;
use Kirki\App\DTO\Page\EditorPagePayloadDTO;
use Kirki\App\DTO\Page\EditPageDTO;
use Kirki\App\DTO\Page\EditPopupDTO;
use Kirki\App\DTO\Page\PagePayloadDTO;
use Kirki\App\DTO\Page\TogglePageSymbolDTO;
use Kirki\App\Http\Requests\Page\PageDataRequest;
use Kirki\App\Http\Requests\Page\PageRequest;
use Kirki\App\Http\Requests\Page\PageUpdateRequest;
use Kirki\App\Http\Requests\Page\PopupRequest;
use Kirki\App\Http\Requests\Page\TogglePageSymbolRequest;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Resources\PageContentResource;
use Kirki\App\Resources\PageResource;
use Kirki\App\Services\PageService;
use Kirki\App\Supports\Facades\Page;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Http\Response;

use function Kirki\Framework\response;

class PageController
{
    /** @var PageService */
    protected $service;

    public function __construct(PageService $service)
    {
        $this->service = $service;
    }

    public function save(PageRequest $request)
    {
        $payload = PagePayloadDTO::from_array($request->sanitized());

        $page = $this->service->save($payload);

        return response()->json([
            'data' => new PageResource($page),
            'message' => __('Page created successfully.', 'kirki'),
        ], Response::OK);
    }

    public function save_page_data(PageDataRequest $request, int $page_id, string $page_content_type)
    {
        $page = PageModel::find($page_id);

        if (empty($page)) {
            throw new Exception(esc_html__('Page not found.', 'kirki'), Response::NOT_FOUND);
        }

        $payload = EditorPagePayloadDTO::from_array($request->sanitized());
        $payload->page = $page;

        return response()->json([
            'data' => [
                'staging_version' => $this->service->save_page_data($payload, $page_content_type),
                'status' => __('Page data saved.', 'kirki'),
            ],
            'message' => __('Page data saved.', 'kirki'),
        ], Response::OK);
    }

    public function publish_staging_version(Request $request)
    {
        $page = PageModel::find($request->int('page_id'));

        if (empty($page)) {
            throw new Exception(esc_html__('Page not found.', 'kirki'), Response::NOT_FOUND);
        }

        return response()->json([
            'data' => Page::publish_stage_version($page->ID),
            'message' => __('Staging version published successfully.', 'kirki'),
        ], Response::OK);
    }

    public function update(PageUpdateRequest $request, int $page_id)
    {
        $page = PageModel::find($page_id);

        if (empty($page)) {
            throw new Exception(esc_html__('Page not found.', 'kirki'), Response::NOT_FOUND);
        }

        $payload = EditPageDTO::from_array($request->sanitized());

        $page = $this->service->update($page, $payload);

        return response()->json([
            'data' => new PageResource($page),
            'message' => __('Page updated successfully.', 'kirki'),
        ], Response::OK);
    }

    public function update_popup(PopupRequest $request, int $popup_id)
    {
        $popup = PageModel::find($popup_id);

        if (empty($popup) || $popup->post_type !== PostTypes::POPUP) {
            throw new Exception(esc_html__('Popup not found.', 'kirki'), Response::NOT_FOUND);
        }

        $payload = EditPopupDTO::from_array($request->sanitized());

        $popup = $this->service->update_popup_data($popup, $payload);

        return response()->json([
            'data' => new PageResource($popup),
            'message' => __('Popup updated successfully.', 'kirki'),
        ], Response::OK);
    }

    public function toggle_disabled_page_symbols(TogglePageSymbolRequest $request)
    {
        $page = PageModel::find($request->int('post_id'));

        if (empty($page)) {
            throw new Exception(esc_html__('Page not found.', 'kirki'), Response::NOT_FOUND);
        }

        $payload = TogglePageSymbolDTO::from_array($request->sanitized());

        return response()->json([
            'data' => [
                'status' => __('Page data saved.', 'kirki'),
                'success' => $this->service->toggle_disabled_page_symbols($payload)
            ],
            'message' => __('Symbol data updated.', 'kirki'),
        ], Response::OK);
    }

    public function duplicate(Request $request)
    {
        $page = PageModel::find($request->int('page_id'));

        if (empty($page)) {
            throw new Exception(esc_html__('Page not found.', 'kirki'), Response::NOT_FOUND);
        }

        return response()->json([
            'data' => new PageResource($this->service->duplicate_page($page)),
            'message' => __('Unused style blocks removed.', 'kirki'),
        ], Response::OK);
    }

    public function delete(Request $request)
    {
        $page = PageModel::find($request->int('page_id'));

        if (empty($page)) {
            throw new Exception(esc_html__('Page not found.', 'kirki'), Response::NOT_FOUND);
        }

        $this->service->delete_page($page);

        return response()->json([
            'data' => [
                'status' => __('Page deleted.', 'kirki'),
            ],
            'message' => __('Page deleted.', 'kirki'),
        ]);
    }

    public function get_page_content(Request $request)
    {
        $page = PageModel::find($request->int('page_id'));

        if (empty($page)) {
            throw new Exception(esc_html__('Page not found.', 'kirki'), Response::NOT_FOUND);
        }

        $stage_version = $request->int('stage_version') ?? false;

        return response()->json([
            'data' => new PageContentResource($page, $stage_version),
        ]);
    }
}
