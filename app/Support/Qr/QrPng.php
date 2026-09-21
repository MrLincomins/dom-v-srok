<?php

declare(strict_types=1);

namespace App\Support\Qr;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;

final class QrPng
{
    public function render(string $content, int $size = 640): string
    {
        return (new Writer(new GDLibRenderer($size)))->writeString($content, 'UTF-8', ErrorCorrectionLevel::M());
    }
}
