<?php

namespace Kirki\App\Http\Controllers\Api;

defined('ABSPATH') || exit;

use Kirki\App\Http\Requests\Post\PostSlugValidationRequest;
use Kirki\App\Services\PostService;
use Kirki\App\Supports\ContentManager;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Http\Response;
use function Kirki\Framework\response;

class PostController
{
    /**
     * @var PostService
     */
    protected $service;

    public function __construct(PostService $service)
    {
        $this->service = $service;
    }
    
    /**
     * Validate WP post slug
     *
     * @since 6.0.14
     *
     * @param Request $request
     *
     * @return Response Returns a JSON response with the validation result.
     */
    public function validate_slug(PostSlugValidationRequest $request)
    {
        $is_valid = ContentManager::validate_slug(
            $request->int('post_id', 0),
            $request->string('post_type'),
            $request->string('post_name')
        );

        return response()->json([
            'data' => $is_valid,
            'message' => $is_valid ? __('Slug validated successfully..', 'kirki') : __('Slug is not available.', 'kirki'),
        ]);
    }

    public function get_all_posts_grouped_by_type(Request $request)
    {
        return response()->json([
            'data' => $this->service->get_all_posts_grouped_by_type($request->string('search', '')),
        ]);
    }
}
