<?php

namespace Kirki\App\Http\Controllers\Api;

use Kirki\App\DTO\CollaborationComment\CreateCollaborationCommentDTO;
use Kirki\App\DTO\CollaborationComment\DeleteCollaborationCommentDTO;
use Kirki\App\DTO\CollaborationComment\ResolveCollaborationCommentDTO;
use Kirki\App\DTO\CollaborationCommentListFilterDTO;
use Kirki\App\Http\Requests\CollaborationComment\CollaborationCommentDestroyRequest;
use Kirki\App\Http\Requests\CollaborationComment\CollaborationCommentResolveRequest;
use Kirki\App\Http\Requests\CollaborationComment\CollaborationCommentStoreRequest;
use Kirki\App\Resources\CollaborationComment\CollaborationCommentResource;
use Kirki\App\Resources\CollaborationComment\CollaborationCommentUserResource;
use Kirki\App\Services\CollaborationCommentService;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Http\Response;

use function Kirki\Framework\response;
use function Kirki\Framework\user;

class CollaborationCommentController
{
    /**
     * @var CollaborationCommentService
     */
    protected $service;

    public function __construct(CollaborationCommentService $service)
    {
        $this->service = $service;
    }

    /**
     * Paginated listing of a post's top-level comments.
     */
    public function index(Request $request)
    {
        $filters = CollaborationCommentListFilterDTO::from_array([
            'post_id' => $request->int('post_id'),
            'page' => $request->int('page', 1),
            'limit' => $request->int('limit', 20),
            'user_id' => user()->get_id()
        ]);

        $paginator = $this->service->paginated($filters);

        return response()->json([
            'data' => CollaborationCommentResource::paginated($paginator),
            'message' => __('Comments retrieved successfully.', 'kirki'),
        ]);
    }

    /**
     * Create a comment or reply.
     */
    public function store(CollaborationCommentStoreRequest $request)
    {
        $dto = CreateCollaborationCommentDTO::from_array($request->validated());
        $dto->user_id = user()->get_id();

        $comment = $this->service->create($dto);

        return response()->json([
            'data' => CollaborationCommentResource::make($comment),
            'message' => __('Comment created successfully.', 'kirki'),
        ], Response::CREATED);
    }

    /**
     * Update a comment's status (e.g. mark resolved).
     */
    public function resolve(CollaborationCommentResolveRequest $request)
    {
        $dto = ResolveCollaborationCommentDTO::from_array($request->validated());
        $dto->user_id = user()->get_id();

        $comment = $this->service->resolve($dto);

        return response()->json([
            'data' => CollaborationCommentResource::make($comment),
            'message' => __('Comment status updated successfully.', 'kirki'),
        ]);
    }

    /**
     * Search users for the @mention picker.
     */
    public function users(Request $request)
    {
        $users = $this->service->search_users(
            $request->string('query', ''),
            $request->int('limit', 20)
        );

        return response()->json([
            'data' => CollaborationCommentUserResource::collection($users),
            'message' => __('Users retrieved successfully.', 'kirki'),
        ]);
    }

    /**
     * Mark a comment as read.
     */
    public function read(Request $request)
    {
        $comment = $this->service->mark_read($request->int('id'), user()->get_id());

        return response()->json([
            'data' => CollaborationCommentResource::make($comment),
            'message' => __('Comment marked as read.', 'kirki'),
        ]);
    }

    /**
     * Mark a comment as unread.
     */
    public function unread(Request $request)
    {
        $comment = $this->service->mark_unread($request->int('id'), user()->get_id());

        return response()->json([
            'data' => CollaborationCommentResource::make($comment),
            'message' => __('Comment marked as unread.', 'kirki'),
        ]);
    }

    /**
     * Delete a comment and its replies (author or editor/administrator only).
     */
    public function destroy(CollaborationCommentDestroyRequest $request)
    {
        $dto = DeleteCollaborationCommentDTO::from_array($request->validated());

        return response()->json([
            'data' => $this->service->delete($dto),
            'message' => __('Comment deleted successfully.', 'kirki'),
        ]);
    }

    /**
     * Mark every top-level comment of a post as read.
     */
    public function read_all(Request $request)
    {
        return response()->json([
            'data' => $this->service->mark_all_read($request->int('post_id'), user()->get_id()),
            'message' => __('All comments marked as read.', 'kirki'),
        ]);
    }

    /**
     * Resolve every top-level comment of a post.
     */
    public function resolve_all(Request $request)
    {
        return response()->json([
            'data' => $this->service->resolve_all($request->int('post_id'), $request->string('session_id', '')),
            'message' => __('All comments marked as resolved.', 'kirki'),
        ]);
    }
}
