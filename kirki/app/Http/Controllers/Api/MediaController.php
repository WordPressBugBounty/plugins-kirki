<?php

namespace Kirki\App\Http\Controllers\Api;

use Kirki\App\DTO\Media\MediaListFilterDTO;
use Kirki\App\Http\Requests\Media\PaginatedMediaListRequest;
use Kirki\App\Resources\Media\MediaResource;
use Kirki\App\Services\MediaService;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Http\Response;
use function Kirki\Framework\response;

defined('ABSPATH') || exit;

class MediaController
{
    /** @var MediaService */
    protected $service;

    public function __construct(MediaService $service)
    {
        $this->service = $service;
    }

    public function paginated(PaginatedMediaListRequest $request)
    {
        $payload = MediaListFilterDTO::from_array($request->validated());

        $paginator = $this->service->paginated($payload);

        return response()->json([
            'data' => MediaResource::paginated($paginator),
            'message' => __('Media data fetched successfully', 'kirki'),
        ], Response::OK);
    }
}
