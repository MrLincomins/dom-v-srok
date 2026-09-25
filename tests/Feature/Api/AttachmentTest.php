<?php

declare(strict_types=1);

use App\Domain\Requests\Enums\AttachmentKind;
use App\Domain\Requests\Models\Attachment;
use App\Domain\Requests\Models\ServiceRequest;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Storage::fake('private');
});

function attachmentTestPhoto(string $path): Attachment
{
    return Attachment::query()->create([
        'request_id' => ServiceRequest::query()->value('id'),
        'kind' => AttachmentKind::Resident,
        'disk' => 'private',
        'path' => $path,
        'mime' => 'image/png',
        'size_bytes' => 5,
    ]);
}

function attachmentTestLink(int $id): string
{
    return URL::temporarySignedRoute('api.attachments.show', now()->addMinutes(5), ['attachment' => $id]);
}

it('serves a stored photo by a signed link without sniffing', function () {
    Storage::disk('private')->put('attachments/ok.png', 'photo');
    $attachment = attachmentTestPhoto('attachments/ok.png');

    $this->get(attachmentTestLink($attachment->id))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('answers 404 when the file is gone from the disk', function () {
    $attachment = attachmentTestPhoto('attachments/missing.png');

    $this->get(attachmentTestLink($attachment->id))
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'not_found');
});

it('checks the signature before looking the attachment up', function () {
    $attachment = attachmentTestPhoto('attachments/any.png');

    $this->get('/api/v1/attachments/'.$attachment->id)->assertStatus(403);
    $this->get('/api/v1/attachments/999999')->assertStatus(403)->assertJsonPath('error.code', 'forbidden');
});
