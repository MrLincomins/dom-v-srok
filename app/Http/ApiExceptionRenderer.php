<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Requests\Exceptions\DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/** любая ошибка в один формат ответа: 401 unauthenticated, 403 forbidden, 404 not_found, 409 конфликт домена, 422 validation_failed, 429 too_many_requests, 500 server_error */
final class ApiExceptionRenderer
{
    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') && ! $request->is('max/*')) {
            return null;
        }

        [$status, $code, $message, $details] = self::describe($e);

        $body = ['error' => ['code' => $code, 'message' => $message]];
        if ($details !== []) {
            $body['error']['details'] = $details;
        }
        if ($status >= 500) {
            $body['error']['request_id'] = (string) $request->header('X-Request-Id', '');
        }

        return new JsonResponse($body, $status);
    }

    /** @return array{0:int,1:string,2:string,3:array<string,mixed>} */
    private static function describe(Throwable $e): array
    {
        return match (true) {
            $e instanceof ValidationException => [422, 'validation_failed', 'Проверьте заполненные поля', ['fields' => $e->errors()]],
            $e instanceof AuthenticationException => [401, 'unauthenticated', 'Нужно войти', []],
            $e instanceof AuthorizationException => [403, 'forbidden', 'Нет доступа', []],
            $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => [404, 'not_found', 'Не найдено', []],
            $e instanceof DomainException => [$e->status(), $e->code(), $e->getMessage(), $e->details()],
            $e instanceof HttpExceptionInterface => [
                $e->getStatusCode(),
                match ($e->getStatusCode()) {
                    401 => 'unauthenticated',
                    403 => 'forbidden',
                    404 => 'not_found',
                    429 => 'too_many_requests',
                    default => 'http_error',
                },
                match ($e->getStatusCode()) {
                    401 => 'Нужно войти',
                    403 => 'Нет доступа',
                    404 => 'Не найдено',
                    429 => 'Слишком много запросов, подождите минуту',
                    default => $e->getMessage() !== '' ? $e->getMessage() : 'Ошибка запроса',
                },
                [],
            ],
            default => [500, 'server_error', config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка, попробуйте позже', []],
        };
    }
}
