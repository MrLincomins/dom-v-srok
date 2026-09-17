<?php

declare(strict_types=1);

use App\Http\Controllers\MaxWebhookController;
use Illuminate\Support\Facades\Route;

// публичная страница, ссылка на бота и как проверять
Route::view('/', 'landing')->name('landing');

// миниапп, одна страница, роутинг на клиенте
Route::view('/app/{any?}', 'miniapp')->where('any', '.*')->name('miniapp');

// вебхук маха, секрет в заголовке, без csrf
Route::post(config('max.webhook_path'), MaxWebhookController::class)
    ->middleware('max.webhook')
    ->name('max.webhook');
