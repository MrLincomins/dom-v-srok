<?php

declare(strict_types=1);

use App\Http\ApiExceptionRenderer;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\VerifyMaxWebhookSecret;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // caddy перед php-fpm передаёт X-Forwarded-*, без этого url и схема будут http
        $middleware->trustProxies(at: '*');
        $middleware->prepend(AssignRequestId::class);
        $middleware->redirectGuestsTo(fn () => null);
        $middleware->validateSignatures(except: ['entrance']);

        // вебхук защищён секретом, csrf ему не нужен
        $middleware->validateCsrfTokens(except: ['max/webhook']);

        $middleware->alias([
            'role' => EnsureRole::class,
            'max.webhook' => VerifyMaxWebhookSecret::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // единый формат ошибок апи: { "error": { "code", "message", "details" } }
        $exceptions->render(fn (Throwable $e, Request $request) => ApiExceptionRenderer::render($e, $request));
    })->create();
