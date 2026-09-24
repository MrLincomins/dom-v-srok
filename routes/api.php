<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\ContractorController;
use App\Http\Controllers\Api\V1\DemoController;
use App\Http\Controllers\Api\V1\ExecutorController;
use App\Http\Controllers\Api\V1\HouseQrController;
use App\Http\Controllers\Api\V1\JournalController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\OrganizationCardController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\RequestController;
use Illuminate\Support\Facades\Route;

// апи миниаппа, контракт в docs/openapi.yaml
// ответы { data } или { data, meta }, ошибки { error: { code, message, details } }
Route::prefix('v1')->name('api.')->middleware('throttle:api')->group(function (): void {
    Route::post('auth/max', [AuthController::class, 'max'])->middleware('throttle:auth')->name('auth.max');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('auth.login');

    Route::get('catalog/categories', [CatalogController::class, 'categories'])->name('catalog.categories');
    Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->middleware('signed')->name('attachments.show');
    Route::get('organization/houses/{house}/qr.png', [HouseQrController::class, 'show'])->middleware('signed')->whereNumber('house')->name('organization.houses.qr');
    Route::get('journal/export.csv', [JournalController::class, 'export'])->middleware('signed')->name('journal.export');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', MeController::class)->name('me');

        Route::get('requests/{serviceRequest}', [RequestController::class, 'show'])->name('requests.show');
        Route::post('requests/{serviceRequest}/confirm', [RequestController::class, 'confirm'])->name('requests.confirm');
        Route::get('my/requests', [RequestController::class, 'my'])->name('my.requests');
        Route::get('organizations/{organization}', [OrganizationCardController::class, 'show'])->whereNumber('organization')->name('organizations.show');

        Route::middleware('role:dispatcher,admin')->group(function (): void {
            Route::get('requests', [RequestController::class, 'index'])->name('requests.index');
            Route::patch('requests/{serviceRequest}/status', [RequestController::class, 'changeStatus'])->name('requests.status');
            Route::post('requests/{serviceRequest}/assign', [RequestController::class, 'assign'])->name('requests.assign');
            Route::post('requests/{serviceRequest}/close', [RequestController::class, 'close'])->name('requests.close');
            Route::post('requests/{serviceRequest}/redirect', [RequestController::class, 'redirect'])->name('requests.redirect');
            Route::post('requests/{serviceRequest}/comments', [RequestController::class, 'comment'])->name('requests.comment');
            Route::post('demo/reset', [DemoController::class, 'reset'])->name('demo.reset');

            Route::get('organization', [OrganizationController::class, 'show'])->name('organization.show');
            Route::patch('organization', [OrganizationController::class, 'update'])->name('organization.update');
            Route::get('organization/houses', [OrganizationController::class, 'houses'])->name('organization.houses');
            Route::patch('organization/houses/{house}', [OrganizationController::class, 'updateHouse'])->whereNumber('house')->name('organization.houses.update');
            Route::get('organization/executors', [ExecutorController::class, 'index'])->name('organization.executors');
            Route::post('organization/executors', [ExecutorController::class, 'store'])->name('organization.executors.store');
            Route::patch('organization/executors/{executor}', [ExecutorController::class, 'update'])->whereNumber('executor')->name('organization.executors.update');
            Route::delete('organization/executors/{executor}', [ExecutorController::class, 'destroy'])->whereNumber('executor')->name('organization.executors.destroy');
            Route::get('organization/contractors', [ContractorController::class, 'index'])->name('organization.contractors');
            Route::post('organization/contractors', [ContractorController::class, 'store'])->name('organization.contractors.store');
            Route::delete('organization/contractors/{contractor}', [ContractorController::class, 'destroy'])->whereNumber('contractor')->name('organization.contractors.destroy');

            Route::get('journal', [JournalController::class, 'index'])->name('journal.index');
            Route::get('journal.csv', [JournalController::class, 'csv'])->name('journal.csv');
            Route::get('journal/csv-link', [JournalController::class, 'csvLink'])->name('journal.csv-link');
        });
    });
});
