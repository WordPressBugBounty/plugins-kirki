<?php

namespace Kirki\App\Http\Controllers\Api;

defined('ABSPATH') || exit;

use Kirki\App\DTO\Collaboration\CreateCollaborationDTO;
use Kirki\App\Http\Requests\CollaborationRequest;
use Kirki\App\Services\CollaborationService;

use function Kirki\Framework\collection;
use function Kirki\Framework\response;

class CollaborationController
{

    /**
     * @var CollaborationService
     */
    protected $service;
    
    public function __construct(CollaborationService $service)
    {
        $this->service = $service;
    }

    public function save_actions(CollaborationRequest $request)
    {
        $payload = collection($request->array('data', []))->map(function ($data) use ($request) {
            return CreateCollaborationDTO::from_array([
                'session_id' => $request->string('session_id'),
                'parent' => $data['parent'] ?? '',
                'parent_id' => $data['parent_id'] ?? 0,
                'data' => $data['action'] ?? [],
            ]);
        });

        return response()->json([
            'data' => $this->service->save_actions($payload),
        ]);
    }
}