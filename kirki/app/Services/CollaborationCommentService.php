<?php

namespace Kirki\App\Services;

use Exception;
use Kirki\App\Constants\AccessLevels;
use Kirki\App\Constants\CollaborationComment\CollaborationCommentResolveStatus;
use Kirki\App\DTO\Collaboration\CreateCollaborationDTO;
use Kirki\App\DTO\CollaborationComment\CreateCollaborationCommentDTO;
use Kirki\App\DTO\CollaborationComment\DeleteCollaborationCommentDTO;
use Kirki\App\DTO\CollaborationComment\ResolveCollaborationCommentDTO;
use Kirki\App\DTO\CollaborationCommentListFilterDTO;
use Kirki\App\Models\CollaborationComment;
use Kirki\App\Models\CollaborationCommentSeen;
use Kirki\App\Resources\CollaborationComment\CollaborationCommentResource;
use Kirki\App\Supports\Role;
use Kirki\Framework\Database\Query\QueryBuilder;
use Kirki\Framework\Exceptions\NotFoundException;
use Throwable;

/**
 * Business logic for canvas/page collaboration comments.
 *
 * Mirrors the legacy KirkiCommentsRest behaviour (including its collaboration
 * broadcast side effects) on top of the CollaborationComment/CollaborationCommentSeen models.
 */
class CollaborationCommentService
{
    protected $collaboration_service;

    public function __construct(CollaborationService $collaboration_service)
    {
        $this->collaboration_service = $collaboration_service;
    }

    /**
     * Paginate the top-level comments of a post.
     *
     * @param CollaborationCommentListFilterDTO $filters
     * @return \Kirki\Framework\Database\Query\Paginator
     */
    public function paginated(CollaborationCommentListFilterDTO $filters)
    {
        $query = CollaborationComment::query()->where('parent_id', 0);

        if (!empty($filters->post_id)) {
            $query->where('post_id', $filters->post_id);
        }

        $query->with(
            [
                'replies' => function (QueryBuilder $query) {
                    $query->oldest();
                },
                'seen' => function (QueryBuilder $query) use ($filters) {
                    $query->where('user_id', $filters->user_id);
                },
            ]
        )->latest();

        return $query->paginate($filters->limit, $filters->page);
    }

    /**
     * Find a single comment with its replies and read status for the given viewer.
     *
     * @param int $id
     * @param int $current_user_id
     * @return CollaborationComment|null
     */
    public function get_single(int $id, int $current_user_id)
    {
        return CollaborationComment::with([
            'replies' => function (QueryBuilder $query) {
                $query->oldest();
            },
            'seen' => function ($query) use ($current_user_id) {
                $query->where('user_id', $current_user_id);
            },
        ])->find($id);
    }

    /**
     * Create a comment (or reply) and broadcast the collaboration update.
     *
     * @param CreateCollaborationCommentDTO $dto
     * @return CollaborationComment
     */
    public function create(CreateCollaborationCommentDTO $dto)
    {
        $data = $dto->all();

        $comment = CollaborationComment::create($data);

        if ($dto->parent_id === 0) {
            CollaborationCommentSeen::create([
                'user_id' => $dto->user_id,
                'comment_id' => $comment->id,
            ]);
        } else {
            CollaborationCommentSeen::where('comment_id', $dto->parent_id)
                ->where_not('user_id', $dto->user_id)
                ->delete();
        }

        $comment = $this->get_single($comment->id, $dto->user_id);

        $data = [
            'type' => 'COLLABORATION_ADD_KIRKI_COMMENT',
            'payload' => [
                'comment' => array_merge(CollaborationCommentResource::make($comment), ['read' => 0]),
            ],
        ];

        $this->collaboration_service->save_action(CreateCollaborationDTO::from_array([
            'parent' => 'post',
            'parent_id' => $dto->post_id,
            'data' => $data,
            'session_id' => $dto->session_id,
        ]));

        return $comment;
    }

    /**
     * Update a comment's status (e.g. mark resolved, unresolved) and broadcast the update.
     *
     * @param ResolveCollaborationCommentDTO $dto
     * @return CollaborationComment|null
     */
    public function resolve(ResolveCollaborationCommentDTO $dto)
    {
        CollaborationComment::where('id', $dto->id)->update(['status' => $dto->status]);

        $data = [
            'type' => 'COLLABORATION_UPDATE_KIRKI_COMMENT',
            'payload' => [
                'data' => ['status' => $dto->status],
                'id' => $dto->id,
            ],
        ];

        $this->collaboration_service->save_action(CreateCollaborationDTO::from_array([
            'parent' => 'post',
            'parent_id' => $dto->post_id,
            'data' => $data,
            'session_id' => $dto->session_id,
        ]));

        return $this->get_single($dto->id, $dto->user_id);
    }

    /**
     * Mark a comment as read by a user.
     *
     * @param int $id
     * @param int $user_id
     * @return CollaborationComment|null
     */
    public function mark_read(int $id, int $user_id)
    {
        $comment = CollaborationComment::find($id);

        if (empty($comment)) {
            throw new NotFoundException((__('No comment found', 'kirki')));
        }

        CollaborationCommentSeen::first_or_create([
            'comment_id' => $id,
            'user_id' => $user_id,
        ]);

        return $this->get_single($id, $user_id);
    }

    /**
     * Mark a comment as unread by a user.
     *
     * @param int $id
     * @param int $user_id
     * @return CollaborationComment|null
     */
    public function mark_unread(int $id, int $user_id)
    {
        CollaborationCommentSeen::where('comment_id', $id)->where('user_id', $user_id)->delete();

        return $this->get_single($id, $user_id);
    }

    /**
     * Delete a comment (and its replies), only when the current user is the
     * author or has an editor/administrator role.
     *
     * @param DeleteCollaborationCommentDTO $dto
     * @return bool|Throwable
     */
    public function delete(DeleteCollaborationCommentDTO $dto)
    {
        CollaborationComment::where('id', $dto->id)->or_where('parent_id', $dto->id)->delete();

        $data = [
            'type' => 'COLLABORATION_DELETE_KIRKI_COMMENT',
            'payload' => ['id' => (string) $dto->id],
        ];

        $this->collaboration_service->save_action(CreateCollaborationDTO::from_array([
            'parent' => 'post',
            'parent_id' => $dto->post_id,
            'data' => $data,
            'session_id' => $dto->session_id,
        ]));

        return true;
    }

    /**
     * Mark every top-level comment of a post as read by a user.
     *
     * @param int $post_id
     * @param int $user_id
     * @return bool
     */
    public function mark_all_read(int $post_id, int $user_id)
    {
        $comment_ids = CollaborationComment::query()
            ->where('post_id', $post_id)
            ->where('parent_id', 0)
            ->pluck('id')
            ->all();

        if (empty($comment_ids)) {
            return false;
        }

        $rows = array_map(function ($comment_id) use ($user_id) {
            return ['user_id' => $user_id, 'comment_id' => $comment_id];
        }, $comment_ids);

        CollaborationCommentSeen::upsert($rows, ['user_id']);

        return true;
    }

    /**
     * Resolve every top-level comment of a post and broadcast the update.
     *
     * @param int $post_id
     * @param string $session_id
     * @return bool
     */
    public function resolve_all(int $post_id, string $session_id)
    {
        $status = CollaborationCommentResolveStatus::RESOLVED;

        CollaborationComment::where('post_id', $post_id)->where('parent_id', 0)->update(['status' => $status]);

        $data = [
            'type' => 'COLLABORATION_UPDATE_ALL_KIRKI_COMMENT',
            'payload' => ['data' => ['status' => $status]],
        ];

        $this->collaboration_service->save_action(CreateCollaborationDTO::from_array([
            'parent' => 'post',
            'parent_id' => $post_id,
            'data' => $data,
            'session_id' => $session_id,
        ]));

        return true;
    }

    /**
     * Search users that have at least view access, for the @mention picker.
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function search_users(string $query, int $limit)
    {
        $roles = Role::get_roles_by_levels([
            AccessLevels::FULL_ACCESS,
            AccessLevels::CONTENT_ACCESS,
            AccessLevels::VIEW_ACCESS,
        ]);

        return get_users([
            'role__in' => $roles,
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => $limit,
            'search' => '*' . $query . '*',
            'search_columns' => ['user_login', 'user_nicename', 'display_name', 'user_email'],
        ]); // @todo: Update this if we plan to use our own User model to handle user related query
    }
}
