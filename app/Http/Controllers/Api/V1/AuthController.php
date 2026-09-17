<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Users\Models\User;
use App\Domain\Users\UserService;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Support\Auth\InitDataValidator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AuthController extends Controller
{
    public function __construct(private readonly InitDataValidator $validator, private readonly UserService $users) {}

    /** вход из миниаппа: initData, проверка подписи, токен. роль из базы */
    public function max(Request $request): JsonResponse
    {
        $data = $request->validate(['init_data' => ['required', 'string', 'max:8192']]);
        $parsed = $this->validator->validate($data['init_data']);

        $user = $this->users->upsertFromMax([
            'user_id' => $parsed['user']['id'],
            'first_name' => $parsed['user']['first_name'] ?? '',
            'last_name' => $parsed['user']['last_name'] ?? '',
            'username' => $parsed['user']['username'] ?? null,
        ]);

        return $this->issueToken($user, 'miniapp');
    }

    /** тестовые учётки для проверки апи, только под флагом и только is_demo */
    public function login(Request $request): JsonResponse
    {
        if (! config('demo.accounts_enabled')) {
            throw new NotFoundHttpException;
        }

        $data = $request->validate([
            'login' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()->where('login', $data['login'])->where('is_demo', true)->first();
        if ($user === null || $user->password === null || ! Hash::check($data['password'], $user->password)) {
            throw new AuthenticationException('Неверный логин или пароль');
        }

        return $this->issueToken($user, 'demo-login');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    private function issueToken(User $user, string $name): JsonResponse
    {
        $expiresAt = now()->addMinutes((int) config('sanctum.expiration', 720));
        $token = $user->createToken($name, ['*'], $expiresAt);

        return response()->json(['data' => [
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => new UserResource($user->load(['organization', 'house'])),
        ]]);
    }
}
