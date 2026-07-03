<?php

namespace Kirki\App\Http\Controllers\Api;

defined('ABSPATH') || exit;

use Exception;
use Kirki\App\DTO\Apps\AppDTO;
use Kirki\App\Http\Requests\Apps\AppsRequest;
use Kirki\App\Http\Requests\Apps\AppSettingsRequest;
use Kirki\App\Services\AppsService;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Http\Response;

use function Kirki\Framework\response;

class AppsController
{
    /** @var AppsService */
    protected $service;

    public function __construct(AppsService $service)
    {
        $this->service = $service;
    }

    public function install_app(AppsRequest $request)
    {
        $payload = AppDTO::from_array($request->sanitized());

        $this->service->install_app($payload);

        return response()->json([
            'data' => __('App installed successfully!', 'kirki'),
            'message' => __('App installed successfully!', 'kirki')
        ]);
    }

    public function get_all_apps(Request $request)
    {
        return response()->json([
            'data' => $this->service->get_all_apps()
        ]);
    }

    public function get_installed_apps(Request $request)
    {
        return response()->json([
            'data' => $this->service->get_installed_apps()
        ]);
    }

    public function get_app_settings_using_slug(Request $request)
    {
        $app_slug = $request->string('slug');

        if (empty($app_slug)) {
            throw new Exception(esc_html__('App slug is required.', 'kirki'), Response::UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $this->service->get_app_settings($app_slug)
        ]);
    }

    public function save_app_settings(AppSettingsRequest $request)
    {
        $this->service->save_app_settings($request->string('slug'), $request->array('settings'));

        return response()->json([
            'data' => true,
            'message' => __('App settings updated successfully!', 'kirki')
        ]);
    }

    public function update_app(AppsRequest $request)
    {
        $payload = AppDTO::from_array($request->sanitized());

        $this->service->update_app($payload);

        return response()->json([
            'data' => __('App updated successfully!', 'kirki'),
            'message' => __('App updated successfully!', 'kirki')
        ]);
    }

    public function remove_app(Request $request)
    {
        $app_slug = $request->string('slug');

        if (empty($app_slug)) {
            throw new Exception(esc_html__('App slug is required.', 'kirki'), Response::UNPROCESSABLE_ENTITY);
        }

        $this->service->delete_app($app_slug);

        return response()->json([
            'data' => 'success',
            'message' => __('App removed successfully!', 'kirki')
        ]);
    }
}