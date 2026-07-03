<?php

namespace Kirki\App\Http\Controllers\Api;

use Kirki\App\Constants\Collection\ActionTypes;
use Kirki\App\DTO\CollectionItemListFilterDTO;
use Kirki\App\DTO\UpsertCollectionItemDTO;
use Kirki\App\Http\Requests\CollectionItem\CollectionItemBulkActionRequest;
use Kirki\App\Http\Requests\CollectionItem\CollectionItemSingleActionRequest;
use Kirki\App\Http\Requests\CollectionItem\CollectionItemStoreRequest;
use Kirki\App\Resources\CollectionItem\CollectionItemResource;
use Kirki\App\Services\CollectionItemService;
use Kirki\Framework\Http\Request;

use Kirki\Framework\Http\Response;
use function Kirki\Framework\response;

class CollectionItemController
{
    /**
     * @var CollectionItemService
     */
    protected $service;

    public function __construct(CollectionItemService $service)
    {
        $this->service = $service;
    }

    /**
     * Paginated listing of a collection's items.
     */
    public function index(Request $request)
    {
        $filters = CollectionItemListFilterDTO::from_array([
            'post_parent' => $request->int('post_parent'),
            'page' => $request->int('page', 1),
            'limit' => $request->int('limit', 20),
            'query' => $request->string('query', ''),
            'exclude_post_ids' => $request->array('exclude_post_ids', []),
        ]);

        $filter_data = $request->array('filter', []);

        if (!empty($filter_data)) {
            $filters->post_status = $filter_data['post_status'] ?? $filters->post_status;
            $filters->post_date = $filter_data['post_date'] ?? $filters->post_date;
            $filters->post_modified = $filter_data['post_modified'] ?? $filters->post_modified;
        }

        $paginated_data = $this->service->paginated($filters);

        return response()->json([
            'data' => CollectionItemResource::paginated($paginated_data),
            'message' => __('Items retrieved successfully.', 'kirki'),
        ]);
    }

    /**
     * Single item.
     */
    public function show(Request $request)
    {
        $item = $this->service->get_single($request->int('item'));

        return response()->json([
            'data' => CollectionItemResource::make($item),
            'message' => __('Item retrieved successfully.', 'kirki'),
        ]);
    }

    /**
     * Create or update an item.
     */
    public function store(CollectionItemStoreRequest $request)
    {
        $dto = UpsertCollectionItemDTO::from_array($request->sanitized());
        $item = $this->service->create_or_update($dto);

        return response()->json([
            'data' => CollectionItemResource::make($item),
            'message' => __('Item saved successfully.', 'kirki'),
        ]);
    }

    /**
     * Delete or duplicate a single item.
     */
    public function action(CollectionItemSingleActionRequest $request)
    {
        $post_id = $request->post_id;
        $action = $request->action;

        $data = false;

        switch ($action) {
            case ActionTypes::DELETE:
                $data = $this->service->delete($post_id);
                break;
            case ActionTypes::DUPLICATE:
                $item = $this->service->duplicate($post_id);
                $data = $item ? CollectionItemResource::make($item) : null;
                break;
            default:
                break;
        }

        return response()->json([
            'data' => $data,
            'message' => __('Action handled successfully.', 'kirki'),
        ]);
    }

    /**
     * Bulk delete or duplicate items.
     */
    public function bulk_action(CollectionItemBulkActionRequest $request)
    {
        $post_ids = $request->array('post_ids', []);
        $parent_id = $request->int('post_parent');
        $action = $request->string('action');

        $data = false;

        switch ($action) {
            case ActionTypes::DELETE:
                $data = $this->service->bulk_delete($post_ids, $parent_id);
                break;
            case ActionTypes::DUPLICATE:
                $data = CollectionItemResource::collection($this->service->bulk_duplicate($post_ids, $parent_id));
                break;
            default:
                break;
        }

        return response()->json([
            'data' => $data,
            'message' => __('Bulk action handled successfully.', 'kirki'),
        ]);
    }
}
