<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Dto\CreateRequestData;
use App\Domain\Requests\Dto\PhotoDraft;
use App\Domain\Requests\Models\Attachment;
use App\Domain\Requests\RequestService;
use App\Domain\Users\Models\User;
use App\Jobs\DownloadMaxAttachment;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function pendingAttachment(): Attachment
{
    $request = app(RequestService::class)->create(new CreateRequestData(
        houseId: (int) House::query()->where('qr_token', DemoSeeder::HOUSE_QR_TOKEN)->value('id'),
        residentUserId: User::query()->where('login', 'demo_resident')->firstOrFail()->id,
        categoryId: (int) Category::query()->where('slug', 'roof.leak')->value('id'),
        photos: [new PhotoDraft('tok-1', 'https://cdn.example/1.png')],
    ));

    return $request->attachments()->firstOrFail();
}

function pngBytes(): string
{
    $image = imagecreatetruecolor(2, 2);
    ob_start();
    imagepng($image);

    return (string) ob_get_clean();
}

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('stores the photo in private storage and fills mime and size', function () {
    Storage::fake('private');
    Http::fake(['https://cdn.example/*' => Http::response(pngBytes(), 200)]);
    $attachment = pendingAttachment();

    app()->call([new DownloadMaxAttachment($attachment->id, 'https://cdn.example/1.png'), 'handle']);

    $attachment->refresh();
    expect($attachment->path)->toStartWith('attachments/'.$attachment->request_id.'/')
        ->and($attachment->mime)->toBe('image/png')
        ->and($attachment->size_bytes)->toBe(strlen((string) Storage::disk('private')->get($attachment->path)));
    Storage::disk('private')->assertExists($attachment->path);
});

it('drops the attachment when MAX returns something that is not an image', function () {
    Storage::fake('private');
    Http::fake(['https://cdn.example/*' => Http::response('not an image', 200)]);
    $attachment = pendingAttachment();

    app()->call([new DownloadMaxAttachment($attachment->id, 'https://cdn.example/1.png'), 'handle']);

    $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
});

it('drops the attachment when the photo is larger than the limit', function () {
    Storage::fake('private');
    config(['attachments.max_mb' => 1]);
    Http::fake(['https://cdn.example/*' => Http::response(pngBytes().str_repeat("\0", 1024 * 1024), 200)]);
    $attachment = pendingAttachment();

    app()->call([new DownloadMaxAttachment($attachment->id, 'https://cdn.example/1.png'), 'handle']);

    $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    expect(Storage::disk('private')->allFiles())->toBe([]);
});

it('removes the empty attachment when every download attempt failed', function () {
    $attachment = pendingAttachment();

    (new DownloadMaxAttachment($attachment->id, 'https://cdn.example/1.png'))->failed(new RuntimeException('MAX не ответил'));

    $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
});
