<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\DemoController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\RequestController;
use Illuminate\Support\Facades\Route;

// апи миниаппа, контракт в docs/openapi.yaml
// ответы { data } или { data, meta }, ошибки { error: { code, message, details } }
Route::prefix('v1')->name('api.')->middleware('throttle:api')->group(function (): void {
    Route::post('auth/max', [AuthController::class, 'max'])->middleware('throttle:auth')->name('auth.max');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('auth.login');

    Route::get('catalog/categories', [CatalogController::class, 'categories'])->name('catalog.categories');
    Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->middleware('signed')->name('attachments.show');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', MeController::class)->name('me');

        Route::get('requests/{serviceRequest}', [RequestController::class, 'show'])->name('requests.show');
        Route::post('requests/{serviceRequest}/confirm', [RequestController::class, 'confirm'])->name('requests.confirm');
        Route::get('my/requests', [RequestController::class, 'my'])->name('my.requests');

        Route::middleware('role:dispatcher,admin')->group(function (): void {
            Route::get('requests', [RequestController::class, 'index'])->name('requests.index');
            Route::patch('requests/{serviceRequest}/status', [RequestController::class, 'changeStatus'])->name('requests.status');
            Route::post('requests/{serviceRequest}/assign', [RequestController::class, 'assign'])->name('requests.assign');
            Route::post('requests/{serviceRequest}/close', [RequestController::class, 'close'])->name('requests.close');
            Route::post('requests/{serviceRequest}/redirect', [RequestController::class, 'redirect'])->name('requests.redirect');
            Route::post('requests/{serviceRequest}/comments', [RequestController::class, 'comment'])->name('requests.comment');
            Route::post('demo/reset', [DemoController::class, 'reset'])->name('demo.reset');
        });
    });
});
