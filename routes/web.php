<?php

declare(strict_types=1);

use App\Http\Controllers\MaxWebhookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

Route::view('/app/{any?}', 'miniapp')->where('any', '.*')->name('miniapp');

Route::post(config('max.webhook_path'), MaxWebhookController::class)
    ->middleware('max.webhook')
    ->name('max.webhook');
