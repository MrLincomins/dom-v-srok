<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Requests\Models\Attachment;
use App\Support\Images\ImageSanitizer;
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
use Throwable;

final class DownloadMaxAttachment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CHUNK_BYTES = 65536;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(public readonly int $attachmentId, public readonly string $url) {}

    public function handle(ImageSanitizer $images): void
    {
        $attachment = Attachment::query()->find($this->attachmentId);
        if ($attachment === null || $attachment->path !== '') {
            return;
        }

        $limit = (int) config('attachments.max_mb') * 1024 * 1024;
        $body = $this->download($limit);
        $mime = $body === null ? '' : (string) (new finfo(FILEINFO_MIME_TYPE))->buffer(substr($body, 0, 4096));
        if ($body === null || $body === '' || ! in_array($mime, (array) config('attachments.mimes'), true)) {
            Log::warning('attachment.rejected', ['attachment_id' => $attachment->id, 'mime' => $mime, 'bytes' => $body === null ? null : strlen($body)]);
            $attachment->delete();

            return;
        }

        $body = $images->sanitize($body, $mime);
        $path = sprintf('attachments/%d/%s.%s', $attachment->request_id, Str::uuid(), ImageSanitizer::extension($mime));
        Storage::disk($attachment->disk)->put($path, $body);
        $attachment->update(['path' => $path, 'mime' => $mime, 'size_bytes' => strlen($body)]);
    }

    public function failed(?Throwable $exception = null): void
    {
        Attachment::query()->whereKey($this->attachmentId)->where('path', '')->delete();
        Log::warning('attachment.download_failed', ['attachment_id' => $this->attachmentId]);
    }

    private function download(int $limit): ?string
    {
        $response = Http::timeout(30)->withOptions(['stream' => true])->get($this->url);
        if (! $response->successful()) {
            throw new RuntimeException("Вложение {$this->attachmentId}: MAX ответил {$response->status()}");
        }

        $declared = $response->header('Content-Length');
        if ($declared !== '' && (int) $declared > $limit) {
            return null;
        }

        $stream = $response->toPsrResponse()->getBody();
        $body = '';
        while (! $stream->eof()) {
            $body .= $stream->read(self::CHUNK_BYTES);
            if (strlen($body) > $limit) {
                $stream->close();

                return null;
            }
        }

        return $body;
    }
}
