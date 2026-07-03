<?php

namespace Kirki\App\Http\Controllers\Api;

use Kirki\App\DTO\ListFilterDTO;
use Kirki\App\DTO\Collection\UpsertCollectionDTO;
use Kirki\App\Http\Requests\Collection\CollectionStoreRequest;
use Kirki\App\Resources\Collection\CollectionResource;
use Kirki\App\Services\CollectionService;
use Kirki\App\Supports\ContentManager;
use Kirki\Framework\Http\Request;

use function Kirki\Framework\response;

class CollectionController
{
    /**
     * @var CollectionService
     */
    protected $service;

    public function __construct(CollectionService $service)
    {
        $this->service = $service;
    }

    /**
     * Paginated listing of collections.
     */
    public function index(Request $request)
    {
        $filters = ListFilterDTO::from_array([
            'page' => $request->int('page', 1),
            'limit' => $request->int('limit', 20),
        ]);

        $collections = $this->service->paginated($filters);

        return response()->json([
            'data' => CollectionResource::paginated($collections),
            'message' => __('Collections retrieved successfully.', 'kirki'),
        ]);
    }

    /**
     * Single collection
     */
    public function show(Request $request)
    {
        $collection = $this->service->get_single($request->int('collection_id'));

        return response()->json([
            'data' => CollectionResource::make($collection),
            'message' => __('Collection retrieved successfully.', 'kirki'),
        ]);
    }

    /**
     * Create or update a collection.
     */
    public function store(CollectionStoreRequest $request)
    {
        $payload = UpsertCollectionDTO::from_array($request->all());
        $collection = $this->service->create_or_update($payload);

        return response()->json([
            'data' => CollectionResource::make($collection),
            'message' => __('Collection saved successfully.', 'kirki'),
        ]);
    }

    /**
     * Delete a collection.
     */
    public function destroy(Request $request)
    {
        return response()->json([
            'data' => $this->service->delete($request->int('collection_id')),
            'message' => __('Collection deleted successfully.', 'kirki'),
        ]);
    }

    /**
     * Duplicate a collection (parent only).
     */
    public function duplicate(Request $request)
    {
        $collection = $this->service->duplicate($request->int('collection_id'));

        return response()->json([
            'data' => CollectionResource::make($collection),
            'message' => __('Collection duplicated successfully.', 'kirki'),
        ]);
    }

    /**
     * Validate a post slug.
     */
    public function validate_slug(Request $request)
    {
        $is_valid = ContentManager::validate_slug(
            $request->int('post_id', 0),
            $request->string('post_type'),
            $request->string('post_name')
        );

        return response()->json([
            'data' => $is_valid,
            'message' => __('Slug validated successfully.', 'kirki'),
        ]);
    }
}
