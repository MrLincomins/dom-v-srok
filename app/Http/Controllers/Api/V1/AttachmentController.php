<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Requests\Models\Attachment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** файлы только по подписанной ссылке, см AttachmentResource */
final class AttachmentController extends Controller
{
    public function show(Attachment $attachment): StreamedResponse
    {
        abort_if($attachment->path === '', 404);

        return Storage::disk($attachment->disk)->response($attachment->path, null, ['Cache-Control' => 'private, max-age=300']);
    }
}
