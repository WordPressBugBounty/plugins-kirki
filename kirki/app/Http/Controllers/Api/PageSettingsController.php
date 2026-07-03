<?php

namespace Kirki\App\Http\Controllers\Api;

defined('ABSPATH') || exit;

use Exception;
use Kirki\App\DTO\Page\PageSettingsDTO;
use Kirki\App\Http\Requests\Page\PageSettingsRequest;
use Kirki\App\Models\Page as PageModel;
use Kirki\App\Resources\PageSettingsResource;
use Kirki\App\Services\PageSettingsService;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Http\Response;

use function Kirki\Framework\response;

class PageSettingsController
{
    /** @var PageSettingsService */
    protected $service;

    public function __construct(PageSettingsService $service)
    {
        $this->service = $service;
    }

    public function update_page_settings(PageSettingsRequest $request, int $page_id)
    {
        $page = PageModel::find($page_id);

        if (empty($page)) {
            throw new Exception(esc_html__('Page not found.', 'kirki'), Response::NOT_FOUND);
        }

        $payload = PageSettingsDTO::from_array($request->sanitized());

        $page = $this->service->update_page_settings($page, $payload);

        return response()->json([
            'data' => PageSettingsResource::make($page),
            'message' => __('Page settings updated.', 'kirki'),
        ]);
    }

    public function update_custom_codes(Request $request)
    {
        $this->service->update_page_custom_code($request->array('custom_code'));

        return response()->json([
            'data' => true,
            'message' => __('Custom code updated.', 'kirki'),
        ]);
    }
}
