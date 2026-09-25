<?php

declare(strict_types=1);

namespace App\Bot\Client;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MaxClient
{
    public function isConfigured(): bool
    {
        return (string) config('max.token') !== '';
    }

    /** @return array<string,mixed> */
    public function getMe(): array
    {
        return $this->call('GET', '/me');
    }

    /**
     * @param  list<array{name:string,description?:string}>  $commands
     * @return array<string,mixed>
     */
    public function setCommands(array $commands): array
    {
        return $this->call('PATCH', '/me/commands', json: ['commands' => $commands]);
    }

    /** @return array<string,mixed> */
    public function subscribe(string $url, array $updateTypes, string $secret): array
    {
        return $this->call('POST', '/subscriptions', json: ['url' => $url, 'update_types' => $updateTypes, 'secret' => $secret]);
    }

    /** @return array<string,mixed> */
    public function unsubscribe(string $url): array
    {
        return $this->call('DELETE', '/subscriptions', query: ['url' => $url]);
    }

    /** @return list<array<string,mixed>> */
    public function subscriptions(): array
    {
        return $this->call('GET', '/subscriptions')['subscriptions'] ?? [];
    }

    /** @return array{updates:list<array<string,mixed>>,marker:int|null} */
    public function getUpdates(?int $marker, int $limit = 100, int $timeout = 30, ?array $types = null): array
    {
        $query = array_filter(['marker' => $marker, 'limit' => $limit, 'timeout' => $timeout, 'types' => $types ? implode(',', $types) : null], fn ($v) => $v !== null);
        $data = $this->call('GET', '/updates', query: $query, timeout: $timeout + 10);

        return ['updates' => $data['updates'] ?? [], 'marker' => isset($data['marker']) ? (int) $data['marker'] : null];
    }

    /** @param array<string,mixed> $body */
    public function sendToUser(int $userId, array $body): array
    {
        return $this->call('POST', '/messages', query: ['user_id' => $userId], json: $this->messageBody($body));
    }

    /** @param array<string,mixed> $body */
    public function sendToChat(int $chatId, array $body): array
    {
        return $this->call('POST', '/messages', query: ['chat_id' => $chatId], json: $this->messageBody($body));
    }

    /** @param array<string,mixed> $body */
    public function editMessage(string $messageId, array $body): array
    {
        return $this->call('PUT', '/messages', query: ['message_id' => $messageId], json: $this->messageBody($body) + ['attachments' => []]);
    }

    /** @param array<string,mixed>|null $message */
    public function answerCallback(string $callbackId, ?string $notification = null, ?array $message = null): array
    {
        $json = array_filter([
            'notification' => $notification,
            'message' => $message !== null ? $this->messageBody($message) : null,
        ], fn ($v) => $v !== null);

        return $this->call('POST', '/answers', query: ['callback_id' => $callbackId], json: $json === [] ? new \stdClass : $json);
    }

    public function pinMessage(int $chatId, string $messageId, bool $notify = true): array
    {
        return $this->call('PUT', "/chats/{$chatId}/pin", json: ['message_id' => $messageId, 'notify' => $notify]);
    }

    public function uploadImage(string $path): string
    {
        $upload = $this->call('POST', '/uploads', query: ['type' => 'image']);
        $url = (string) ($upload['url'] ?? '');
        if ($url === '') {
            throw new MaxApiException('POST /uploads не вернул url', 0, 'POST /uploads');
        }

        $response = $this->pending()->attach('data', file_get_contents($path) ?: '', basename($path))->post($url);
        $data = $this->decode($response, 'POST upload');

        $token = $this->findToken($data);
        if ($token === null) {
            throw new MaxApiException('Загрузка изображения не вернула token', 0, 'POST upload');
        }

        return $token;
    }

    /** @param array<string,mixed> $body */
    private function messageBody(array $body): array
    {
        $attachments = [];
        foreach ($body['keyboard'] ?? [] as $attachment) {
            $attachments[] = $attachment;
        }
        foreach ($body['attachments'] ?? [] as $attachment) {
            $attachments[] = $attachment;
        }

        return array_filter([
            'text' => $body['text'] ?? null,
            'attachments' => $attachments === [] ? null : $attachments,
            'format' => $body['format'] ?? null,
            'notify' => $body['notify'] ?? null,
        ], fn ($v) => $v !== null);
    }

    private function pending(?int $timeout = null): PendingRequest
    {
        $request = Http::baseUrl((string) config('max.api_base'))
            ->withHeaders(['Authorization' => (string) config('max.token')])
            ->acceptJson()
            ->timeout($timeout ?? (int) config('max.timeout'))
            ->connectTimeout(5);

        $bundle = (string) config('max.ca_bundle');
        if ($bundle !== '') {
            $request = $request->withOptions(['verify' => base_path($bundle)]);
        }

        return $request;
    }

    /** @return array<string,mixed> */
    private function call(string $method, string $path, array $query = [], array|\stdClass|null $json = null, ?int $timeout = null): array
    {
        if (! $this->isConfigured()) {
            throw new MaxApiException('MAX_BOT_TOKEN не задан', 0, "$method $path");
        }

        try {
            $request = $this->pending($timeout)->withQueryParameters($query);
            $response = match ($method) {
                'GET' => $request->get($path),
                'POST' => $request->post($path, $json ?? []),
                'PUT' => $request->put($path, $json ?? []),
                'PATCH' => $request->patch($path, $json ?? []),
                'DELETE' => $request->delete($path),
                default => throw new \InvalidArgumentException($method),
            };
        } catch (ConnectionException $e) {
            throw new MaxApiException('MAX недоступен: '.$e->getMessage(), 0, "$method $path");
        }

        return $this->decode($response, "$method $path");
    }

    /** @return array<string,mixed> */
    private function decode(Response $response, string $label): array
    {
        if ($response->status() === 429) {
            throw new MaxRateLimited('MAX: превышен лимит запросов', 429, $label);
        }
        if ($response->failed()) {
            $message = $response->json('message') ?? $response->json('error') ?? mb_strimwidth($response->body(), 0, 300);
            throw new MaxApiException(sprintf('%s → %d: %s', $label, $response->status(), is_string($message) ? $message : json_encode($message)), $response->status(), $label);
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    private function findToken(mixed $data): ?string
    {
        if (! is_array($data)) {
            return null;
        }
        if (isset($data['token']) && is_string($data['token'])) {
            return $data['token'];
        }
        foreach ($data as $value) {
            $found = $this->findToken($value);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }
}
