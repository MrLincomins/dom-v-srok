<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** мах присылает секрет в заголовке X-Max-Bot-Api-Secret, не совпал - 403 */
final class VerifyMaxWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('max.webhook_secret');
        $given = (string) $request->header('X-Max-Bot-Api-Secret', '');

        if ($expected === '' || ! hash_equals($expected, $given)) {
            return response()->noContent(403);
        }

        return $next($request);
    }
}
