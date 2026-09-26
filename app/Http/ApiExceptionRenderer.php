<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Requests\Exceptions\DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class ApiExceptionRenderer
{
    private const HTTP_CODES = [
        401 => 'unauthenticated',
        403 => 'forbidden',
        404 => 'not_found',
        429 => 'too_many_requests',
    ];

    private const HTTP_MESSAGES = [
        400 => 'Неверный запрос',
        401 => 'Нужно войти',
        403 => 'Нет доступа',
        404 => 'Не найдено',
        405 => 'Метод не поддерживается',
        413 => 'Слишком большой запрос',
        415 => 'Неподдерживаемый формат данных',
        429 => 'Слишком много запросов, подождите минуту',
        503 => 'Сервис временно недоступен, попробуйте позже',
    ];

    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') && ! $request->is('max/*')) {
            return null;
        }
        if ($e instanceof HttpResponseException) {
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

        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

        return new JsonResponse($body, $status, $headers);
    }

    /** @return array{0:int,1:string,2:string,3:array<string,mixed>} */
    private static function describe(Throwable $e): array
    {
        return match (true) {
            $e instanceof ValidationException => [422, 'validation_failed', 'Проверьте заполненные поля', ['fields' => $e->errors()]],
            $e instanceof AuthenticationException => [401, 'unauthenticated', $e->getMessage() !== 'Unauthenticated.' ? $e->getMessage() : 'Нужно войти', []],
            $e instanceof DomainException => [$e->status(), $e->code(), $e->getMessage(), $e->details()],
            $e instanceof HttpExceptionInterface => [
                $e->getStatusCode(),
                self::HTTP_CODES[$e->getStatusCode()] ?? 'http_error',
                self::HTTP_MESSAGES[$e->getStatusCode()] ?? 'Ошибка запроса',
                [],
            ],
            default => [500, 'server_error', config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка, попробуйте позже', []],
        };
    }
}
