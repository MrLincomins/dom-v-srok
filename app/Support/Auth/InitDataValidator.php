<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Auth\AuthenticationException;

/** проверка initData как в доке маха: пары key=value без hash, url-декод, сортировка по ключу, склейка через \n, secret = hmac(WebAppData, токен), подпись = hmac(secret, строка), сравниваем с hash */
final class InitDataValidator
{
    /**
     * @return array{user:array<string,mixed>,auth_date:int,query_id:string|null,start_param:string|null,chat:array<string,mixed>|null}
     *
     * @throws AuthenticationException
     */
    public function validate(string $initData, ?string $botToken = null, ?int $ttl = null): array
    {
        $botToken ??= (string) config('max.token');
        $ttl ??= (int) config('max.init_data_ttl');
        if ($botToken === '') {
            throw new AuthenticationException('Бот не настроен');
        }

        $pairs = [];
        $hash = null;
        foreach (explode('&', $initData) as $chunk) {
            if ($chunk === '') {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $chunk, 2), 2, '');
            $key = urldecode($key);
            $value = urldecode($value);
            if ($key === 'hash') {
                if ($hash !== null) {
                    throw new AuthenticationException('initData: hash встречается дважды');
                }
                $hash = $value;

                continue;
            }
            if (isset($pairs[$key])) {
                throw new AuthenticationException('initData: параметр '.$key.' встречается дважды');
            }
            $pairs[$key] = $value;
        }

        if ($hash === null || $pairs === []) {
            throw new AuthenticationException('initData: нет подписи');
        }

        ksort($pairs, SORT_STRING);
        $launchParams = implode("\n", array_map(fn ($k, $v) => $k.'='.$v, array_keys($pairs), $pairs));
        $secret = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $signature = hash_hmac('sha256', $launchParams, $secret);

        if (! hash_equals($signature, strtolower($hash))) {
            throw new AuthenticationException('initData: подпись не совпала');
        }

        $authDate = (int) ($pairs['auth_date'] ?? 0);
        if ($authDate <= 0 || time() - $authDate > $ttl) {
            throw new AuthenticationException('initData устарел, откройте мини-приложение заново');
        }

        $user = json_decode($pairs['user'] ?? '', true);
        if (! is_array($user) || ! isset($user['id'])) {
            throw new AuthenticationException('initData: нет пользователя');
        }
        $chat = isset($pairs['chat']) ? json_decode($pairs['chat'], true) : null;

        return [
            'user' => $user,
            'auth_date' => $authDate,
            'query_id' => $pairs['query_id'] ?? null,
            'start_param' => $pairs['start_param'] ?? null,
            'chat' => is_array($chat) ? $chat : null,
        ];
    }

    /** собрать подписанный initData для тестов и локалки */
    public static function sign(array $params, string $botToken): string
    {
        ksort($params, SORT_STRING);
        $launchParams = implode("\n", array_map(fn ($k, $v) => $k.'='.$v, array_keys($params), $params));
        $secret = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $params['hash'] = hash_hmac('sha256', $launchParams, $secret);

        return http_build_query($params, '', '&', PHP_QUERY_RFC1738);
    }
}
