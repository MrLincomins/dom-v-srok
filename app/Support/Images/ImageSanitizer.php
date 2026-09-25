<?php

declare(strict_types=1);

namespace App\Support\Images;

use GdImage;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class ImageSanitizer
{
    private const JPEG_QUALITY = 88;

    private const WEBP_QUALITY = 88;

    private const MAX_PIXELS = 50_000_000;

    public static function extension(string $mime): string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    public function sanitize(string $bytes, string $mime): string
    {
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return $bytes;
        }

        try {
            $image = $this->decode($bytes);
            if ($mime === 'image/jpeg') {
                $image = $this->orient($image, $this->jpegOrientation($bytes));
            }

            return $this->encode($image, $mime);
        } catch (Throwable $e) {
            Log::warning('image.sanitize_failed', ['mime' => $mime, 'bytes' => strlen($bytes), 'reason' => $e->getMessage()]);

            return $bytes;
        }
    }

    private function decode(string $bytes): GdImage
    {
        $size = @getimagesizefromstring($bytes);
        if ($size === false || $size[0] * $size[1] > self::MAX_PIXELS) {
            throw new RuntimeException('unsupported image size');
        }
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw new RuntimeException('decode failed');
        }

        return $image;
    }

    private function encode(GdImage $image, string $mime): string
    {
        $stream = fopen('php://temp', 'w+b');
        if ($stream === false) {
            throw new RuntimeException('no temp stream');
        }

        try {
            if ($mime !== 'image/jpeg') {
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }
            $written = match ($mime) {
                'image/png' => imagepng($image, $stream),
                'image/webp' => imagewebp($image, $stream, self::WEBP_QUALITY),
                default => imagejpeg($image, $stream, self::JPEG_QUALITY),
            };
            rewind($stream);
            $result = stream_get_contents($stream);
        } finally {
            fclose($stream);
        }

        if (! $written || $result === false || $result === '') {
            throw new RuntimeException('encode failed');
        }

        return $result;
    }

    private function orient(GdImage $image, int $orientation): GdImage
    {
        $angle = match ($orientation) {
            3, 4 => 180,
            5, 6 => 270,
            7, 8 => 90,
            default => 0,
        };
        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);
            if ($rotated === false) {
                throw new RuntimeException('rotate failed');
            }
            $image = $rotated;
        }
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        return $image;
    }

    private function jpegOrientation(string $bytes): int
    {
        $length = strlen($bytes);
        $offset = 2;
        while ($offset + 4 <= $length && $bytes[$offset] === "\xFF") {
            $marker = ord($bytes[$offset + 1]);
            if ($marker === 0xDA || $marker === 0xD9) {
                break;
            }
            $segment = $this->unpackInt('n', substr($bytes, $offset + 2, 2));
            if ($marker === 0xE1 && substr($bytes, $offset + 4, 6) === "Exif\0\0") {
                return $this->tiffOrientation(substr($bytes, $offset + 10, max(0, $segment - 8)));
            }
            $offset += 2 + $segment;
        }

        return 1;
    }

    private function tiffOrientation(string $tiff): int
    {
        [$short, $long] = match (substr($tiff, 0, 2)) {
            'II' => ['v', 'V'],
            'MM' => ['n', 'N'],
            default => [null, null],
        };
        if ($short === null || strlen($tiff) < 8) {
            return 1;
        }

        $ifd = $this->unpackInt($long, substr($tiff, 4, 4));
        $count = $this->unpackInt($short, substr($tiff, $ifd, 2));
        for ($i = 0; $i < $count; $i++) {
            $entry = $ifd + 2 + $i * 12;
            if ($entry + 12 > strlen($tiff)) {
                break;
            }
            if ($this->unpackInt($short, substr($tiff, $entry, 2)) === 0x0112) {
                $value = $this->unpackInt($short, substr($tiff, $entry + 8, 2));

                return $value >= 1 && $value <= 8 ? $value : 1;
            }
        }

        return 1;
    }

    private function unpackInt(string $format, string $bytes): int
    {
        $expected = in_array($format, ['n', 'v'], true) ? 2 : 4;
        if (strlen($bytes) !== $expected) {
            return 0;
        }
        $value = unpack($format, $bytes);

        return is_array($value) ? (int) $value[1] : 0;
    }
}
