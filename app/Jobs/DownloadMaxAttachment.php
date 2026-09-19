<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Requests\Models\Attachment;
use finfo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/** фотка жителя из маха в storage, пока не скачана у вложения path пустой */
final class DownloadMaxAttachment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(public readonly int $attachmentId, public readonly string $url) {}

    public function handle(): void
    {
        $attachment = Attachment::query()->find($this->attachmentId);
        if ($attachment === null || $attachment->path !== '') {
            return;
        }

        $response = Http::timeout(30)->get($this->url);
        if (! $response->successful()) {
            throw new RuntimeException("Вложение {$this->attachmentId}: MAX ответил {$response->status()}");
        }

        $body = $response->body();
        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->buffer($body);
        $limit = (int) config('attachments.max_mb') * 1024 * 1024;
        if ($body === '' || strlen($body) > $limit || ! in_array($mime, (array) config('attachments.mimes'), true)) {
            Log::warning('attachment.rejected', ['attachment_id' => $attachment->id, 'mime' => $mime, 'bytes' => strlen($body)]);
            $attachment->delete();

            return;
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $path = sprintf('attachments/%d/%s.%s', $attachment->request_id, Str::uuid(), $extension);
        Storage::disk($attachment->disk)->put($path, $body);
        $attachment->update(['path' => $path, 'mime' => $mime, 'size_bytes' => strlen($body)]);
    }
}
