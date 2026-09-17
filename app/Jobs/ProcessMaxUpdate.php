<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bot\UpdateDispatcher;
use App\Bot\Updates\Update;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/** обработка одного обновления маха вне http, вебхук уже ответил 200 */
final class ProcessMaxUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 30];

    /** @param array<string,mixed> $raw */
    public function __construct(public readonly array $raw) {}

    public function handle(UpdateDispatcher $dispatcher): void
    {
        $update = Update::fromArray($this->raw);
        Log::info('bot.update', ['type' => $update->type, 'key' => $update->key()]);
        $dispatcher->dispatch($update);
    }
}
