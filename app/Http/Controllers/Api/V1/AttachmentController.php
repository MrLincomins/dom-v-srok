<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Requests\Models\Attachment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AttachmentController extends Controller
{
    public function show(Attachment $attachment): StreamedResponse
    {
        $disk = Storage::disk($attachment->disk);
        abort_if($attachment->path === '' || ! $disk->exists($attachment->path), 404);

        return $disk->response($attachment->path, null, [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
