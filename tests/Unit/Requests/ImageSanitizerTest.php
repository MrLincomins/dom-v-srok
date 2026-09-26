<?php

declare(strict_types=1);

use App\Support\Images\ImageSanitizer;

function jpegWithExif(int $width, int $height, int $orientation): string
{
    $image = imagecreatetruecolor($width, $height);
    ob_start();
    imagejpeg($image);
    $jpeg = (string) ob_get_clean();

    $tiff = 'MM'.pack('n', 42).pack('N', 8)
        .pack('n', 2)
        .pack('nnN', 0x0112, 3, 1).pack('n', $orientation).pack('n', 0)
        .pack('nnN', 0x8825, 4, 1).pack('N', 0)
        .pack('N', 0);
    $payload = "Exif\0\0".$tiff;
    $app1 = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

    return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
}

it('removes exif from a jpeg and keeps it an image', function () {
    $original = jpegWithExif(4, 2, 1);
    expect($original)->toContain("Exif\0\0");

    $clean = (new ImageSanitizer)->sanitize($original, 'image/jpeg');

    expect($clean)->not->toContain('Exif')
        ->and(getimagesizefromstring($clean)['mime'] ?? null)->toBe('image/jpeg');
});

it('turns the picture upright before dropping the orientation tag', function () {
    $clean = (new ImageSanitizer)->sanitize(jpegWithExif(4, 2, 6), 'image/jpeg');

    $size = getimagesizefromstring($clean);
    expect([$size[0] ?? null, $size[1] ?? null])->toBe([2, 4]);
});

it('returns the original bytes when the image cannot be decoded', function () {
    expect((new ImageSanitizer)->sanitize('not an image', 'image/png'))->toBe('not an image');
});
