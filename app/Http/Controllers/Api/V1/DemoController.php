<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Demo\DemoResetService;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class DemoController extends Controller
{
    public function reset(Request $request, DemoResetService $service): JsonResponse
    {
        $user = $request->user();
        $organization = $user->organization;
        if ($organization === null || ! $organization->is_demo) {
            throw new AuthorizationException('Сброс доступен только для демо-организации');
        }
        $service->reset();
        Log::info('demo.reset', ['user_id' => $user->id, 'organization_id' => $organization->id]);

        return response()->json(['data' => ['ok' => true]]);
    }
}
