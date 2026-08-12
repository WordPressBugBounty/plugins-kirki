<?php

namespace Kirki\App\Http\Controllers\Api;

defined('ABSPATH') || exit;

use Kirki\Framework\Http\Request;
use Kirki\App\Services\FormSubmissionService;
use function Kirki\Framework\response;

/**
 * Handles front-end form submissions.
 *
 * Route: POST /kirki/v1/frontend/form (public).
 */
class FormController
{
	/**
	 * @var FormSubmissionService
	 */
	protected $service;

	/**
	 * @param FormSubmissionService $service
	 */
	public function __construct(FormSubmissionService $service)
	{
		$this->service = $service;
	}

	/**
	 * Store a form submission.
	 *
	 * @param Request $request The submission request.
	 * @return \Kirki\Framework\Http\JsonResponse
	 */
	public function store(Request $request)
	{
		$result = $this->service->handle($request->all());

		return response()->json([
			'data' => $result,
			'message' => __('Form submitted successfully.', 'kirki'),
		]);
	}
}
