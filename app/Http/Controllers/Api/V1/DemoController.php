<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Demo\DemoResetService;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DemoController extends Controller
{
    /** сброс демо данных, только сотрудник демо организации */
    public function reset(Request $request, DemoResetService $service): JsonResponse
    {
        $organization = $request->user()->organization;
        if ($organization === null || ! $organization->is_demo) {
            throw new AuthorizationException('Сброс доступен только для демо-организации');
        }
        $service->reset();

        return response()->json(['data' => ['ok' => true]]);
    }
}
