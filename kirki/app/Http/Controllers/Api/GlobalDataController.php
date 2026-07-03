<?php

namespace Kirki\App\Http\Controllers\Api;

defined('ABSPATH') || exit;

use Exception;
use Kirki\App\DTO\GoogleFontDTO;
use Kirki\App\Http\Requests\DataRequest;
use Kirki\App\Http\Requests\DownloadGoogleFontRequest;
use Kirki\App\Services\FontService;
use Kirki\App\Supports\Facades\GlobalData;
use Kirki\Framework\Http\Request;

use function Kirki\Framework\response;

class GlobalDataController
{
    public function get_ui_controller(Request $request)
    {
        return response()->json([
            'data' => GlobalData::get_global_ui_controller(),
        ]);
    }

    public function update_ui_controller(DataRequest $request)
    {
        GlobalData::update_global_ui_controller($request->array('data'));

        return response()->json([
            'data' => [
                'status' => __('UI controller data updated', 'kirki'),
            ],
            'message' => __('UI controller data updated', 'kirki'),
        ]);
    }

    public function get_ui_saved_data(Request $request) {
        return response()->json([
            'data' => GlobalData::get_global_ui_saved_data(),
        ]);
    }

    public function update_ui_saved_data(DataRequest $request)
    {
        GlobalData::update_global_ui_saved_data($request->array('data'));

        return response()->json([
            'data' => [
                'status' => __('UI saved data updated', 'kirki'),
            ],
            'message' => __('UI saved data updated', 'kirki'),
        ]);
    }

    public function get_custom_fonts(Request $request)
    {
        return response()->json([
            'data' => GlobalData::get_global_custom_fonts(),
        ]);
    }

    public function update_custom_fonts(DataRequest $request)
    {
        GlobalData::update_global_custom_fonts($request->array('data'));

        return response()->json([
            'data' => [
                'status' => __('Custom fonts data updated', 'kirki'),
            ],
            'message' => __('Custom fonts data updated', 'kirki'),
        ]);
    }

    public function download_google_font_offline(DownloadGoogleFontRequest $request)
    {
        $payload = GoogleFontDTO::from_array($request->array('font'));

        return response()->json([
            'data' => [
                'font'    => FontService::create()->download_google_font($payload),
				'status'  => true,
				'message' => __('Font downloaded successfully', 'kirki'),
            ],
            'message' => __('Font downloaded successfully', 'kirki'),
        ]);
    }

    public function remove_google_font_offline(DownloadGoogleFontRequest $request) {
        $payload = GoogleFontDTO::from_array($request->array('font'));

        FontService::create()->remove_google_font($payload);

        return response()->json([
            'data' => [
                'font'    => $payload->to_array(),
                'status'  => true,
                'message' => __('Font removed successfully', 'kirki'),
            ],
            'message' => __('Font removed successfully', 'kirki'),
        ]);
    }

    public function remove_custom_fonts_permanently(DataRequest $request) {
        $fonts = $request->array('data', []);

         FontService::create()->remove_custom_fonts_permanently($fonts);

        return response()->json([
            'data' => [
                'status'  => 'success',
				'message' => __('Font folder deleted success', 'kirki'),
            ],
        ]);
    }
}
