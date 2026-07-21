<?php

defined('ABSPATH') || exit;

use Kirki\App\Constants\PageContentTypes;
use Kirki\App\Supports\Role;
use Kirki\App\Http\Controllers\Api\AppsController;
use Kirki\App\Http\Controllers\Api\MediaController;
use Kirki\App\Http\Controllers\Api\PageController;
use Kirki\App\Http\Controllers\Api\CollectionController;
use Kirki\App\Http\Controllers\Api\CollaborationCommentController;
use Kirki\App\Http\Middlewares\EditAccessMiddleware;
use Kirki\App\Http\Middlewares\FullAccessMiddleware;
use Kirki\App\Http\Controllers\Api\CollectionItemController;
use Kirki\App\Http\Controllers\Api\EditorController;
use Kirki\App\Http\Controllers\Api\GlobalDataController;
use Kirki\App\Http\Controllers\Api\PageSettingsController;
use Kirki\App\Http\Controllers\Api\UserController;
use Kirki\App\Http\Middlewares\ViewAccessMiddleware;
use Kirki\App\Http\Middlewares\ViewOrPreviewMiddleware;
use Kirki\Framework\Http\Request;
use Kirki\Framework\Route;
use Kirki\Framework\Supports\Arr;

use function Kirki\Framework\app;
use function Kirki\Framework\response;

Route::set_namespace('kirki/v1');

Route::get('/ping', function (Request $request) {
    return response()->json([
        'is_working' => true,
    ]);
});

Route::get('/is-user-logged-in', [UserController::class, 'is_logged_in']);

Route::group([
    'middleware' => [
        EditAccessMiddleware::class
    ]
], function () {
    Route::post('/pages/{page_id}/{page_content_type}', [PageController::class, 'save_page_data'])
        ->where('page_id', '[\d]+')
        ->where(
            'page_content_type', 
            '(?:' . Arr::join([
                PageContentTypes::BLOCKS, 
                PageContentTypes::STYLES, 
                PageContentTypes::USED_STYLES, 
                PageContentTypes::USED_STYLE_IDS_RANDOM, 
                PageContentTypes::USED_FONTS
            ], '|') . ')'
        );
    Route::put('/pages/{page_id}', [PageController::class, 'update'])->where('page_id', '[\d]+');
    Route::put('/popups/{popup_id}', [PageController::class, 'update_popup'])->where('popup_id', '[\d]+');

    // Global data routes
    Route::put('/global-ui-controller', [GlobalDataController::class, 'update_ui_controller']);
    Route::put('/global-ui-saved-data', [GlobalDataController::class, 'update_ui_saved_data']);
    Route::post('/global-styles', [GlobalDataController::class, 'save_global_styles']);
});

Route::group([
    'middleware' => [
        FullAccessMiddleware::class
    ]
], function () {
    Route::post('/pages', [PageController::class, 'save']);
    Route::post('/pages/{page_id}/publish-staging-version', [PageController::class, 'publish_staging_version'])->where('page_id', '[\d]+');
    Route::post('toggle-disabled-page-symbols', [PageController::class, 'toggle_disabled_page_symbols']);
    Route::post('/pages/{page_id}/duplicate', [PageController::class, 'duplicate'])->where('page_id', '[\d]+');
    Route::delete('/pages/{page_id}', [PageController::class, 'delete'])->where('page_id', '[\d]+');
    Route::put('/pages/{page_id}/settings', [PageSettingsController::class, 'update_page_settings'])->where('page_id', '[\d]+');
    Route::put('/custom-code-data', [PageSettingsController::class, 'update_custom_codes']);

    Route::post('/back-to-kirki-editor', [EditorController::class, 'back_to_kirki_editor']);
    Route::post('/back-to-wordpress-editor', [EditorController::class, 'back_to_wordpress_editor']);

    // Global data routes
    Route::put('/global-custom-fonts', [GlobalDataController::class, 'update_custom_fonts']);
    Route::post('/download-google-font-offline', [GlobalDataController::class, 'download_google_font_offline']);
    Route::delete('/remove-google-font-offline', [GlobalDataController::class, 'remove_google_font_offline']);
    Route::delete('/remove-custom-font-permanently', [GlobalDataController::class, 'remove_custom_fonts_permanently']);

    // Apps routes
    Route::post('/install-app', [AppsController::class, 'install_app']);
    Route::put('/update-app', [AppsController::class, 'update_app']);
    Route::delete('/remove-app', [AppsController::class, 'remove_app']);
    Route::put('/app-settings', [AppsController::class, 'save_app_settings']);
    Route::get('/app-settings', [AppsController::class, 'get_app_settings_using_slug']);
});

Route::group(['middleware' => ViewOrPreviewMiddleware::class], function () {
    // Page Routes
    Route::get('/pages/{page_id}', [PageController::class, 'get_page_content'])->where('page_id', '[\d]+');

    // Apps routes
    Route::get('/app-list', [AppsController::class, 'get_all_apps']);
    Route::get('/installed-app-list', [AppsController::class, 'get_installed_apps']);

    // Global data routes
    Route::get('/global-ui-controller', [GlobalDataController::class, 'get_ui_controller']);
    Route::get('/global-ui-saved-data', [GlobalDataController::class, 'get_ui_saved_data']);
    Route::get('/global-custom-fonts', [GlobalDataController::class, 'get_custom_fonts']);
});

// Content Manager — Collection Routes
Route::group(['middleware' => ViewOrPreviewMiddleware::class], function () {
    Route::get('/content-manager/collections', [CollectionController::class, 'index']);
    Route::get('/content-manager/collections/{collection_id}', [CollectionController::class, 'show'])
        ->where('collection_id', '\d+');
    Route::get('/content-manager/validate_slug', [CollectionController::class, 'validate_slug'])->middleware(ViewAccessMiddleware::class);
});

Route::group(['middleware' => EditAccessMiddleware::class], function () {
    Route::post('/content-manager/collections', [CollectionController::class, 'store']);
    Route::delete('/content-manager/collections/{collection_id}', [CollectionController::class, 'destroy'])->where('collection_id', '\d+');
    Route::post('/content-manager/collections/{collection_id}/duplicate', [CollectionController::class, 'duplicate'])
        ->where('collection_id', '\d+');
});

// Content Manager — Collection Item Routes
Route::group(['middleware' => ViewOrPreviewMiddleware::class], function () {
    Route::get('/content-manager/collections/items', [CollectionItemController::class, 'index']);
    Route::get('/content-manager/collections/items/{item}', [CollectionItemController::class, 'show'])
        ->where('item', '\d+');
});

Route::group(['middleware' => EditAccessMiddleware::class], function () {
    Route::post('/content-manager/collections/items', [CollectionItemController::class, 'store']);
    Route::post('/content-manager/collections/items/action', [CollectionItemController::class, 'action']);
    Route::post('/content-manager/collections/items/bulk-action', [CollectionItemController::class, 'bulk_action']);
});

// Media Route
Route::get('/media', [MediaController::class, 'paginated'])->middleware(EditAccessMiddleware::class);

// Comment Routes
Route::group(['middleware' => ViewAccessMiddleware::class], function () {
    Route::get('/collaboration-comments', [CollaborationCommentController::class, 'index']);
    Route::get('/collaboration-comments/all-users', [CollaborationCommentController::class, 'users']);
});

Route::get('/collaboration-comments/all-users', [CollaborationCommentController::class, 'users'])->middleware(ViewAccessMiddleware::class);

Route::group(['middleware' => EditAccessMiddleware::class], function () {
    Route::post('/collaboration-comments', [CollaborationCommentController::class, 'store']);
    Route::put('/collaboration-comments/resolve', [CollaborationCommentController::class, 'resolve']);
    Route::put('/collaboration-comments/read', [CollaborationCommentController::class, 'read']);
    Route::put('/collaboration-comments/unread', [CollaborationCommentController::class, 'unread']);
    Route::delete('/collaboration-comments/delete', [CollaborationCommentController::class, 'destroy']);
    Route::put('/collaboration-comments/read-all', [CollaborationCommentController::class, 'read_all']);
    Route::put('/collaboration-comments/resolve-all', [CollaborationCommentController::class, 'resolve_all']);
});
