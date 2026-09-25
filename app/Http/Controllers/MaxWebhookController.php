<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Bot\Models\ProcessedUpdate;
use App\Bot\Updates\Update;
use App\Jobs\ProcessMaxUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MaxWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $raw = $request->json()->all();
        if (! is_array($raw) || ! isset($raw['update_type'])) {
            return response()->json(['ok' => false, 'error' => 'not an update'], 400);
        }

        $update = Update::fromArray($raw);
        $inserted = ProcessedUpdate::query()->insertOrIgnore(['update_key' => $update->key(), 'received_at' => now()]);

        if ($inserted > 0) {
            try {
                ProcessMaxUpdate::dispatch($raw);
            } catch (\Throwable $e) {
                ProcessedUpdate::query()->whereKey($update->key())->delete();
                throw $e;
            }
        }

        return response()->json(['ok' => true, 'duplicate' => $inserted === 0]);
    }
}
