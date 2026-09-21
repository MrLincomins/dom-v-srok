<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Organizations\DeepLinks;
use App\Domain\Organizations\Models\House;
use App\Http\Controllers\Controller;
use App\Support\Qr\QrPng;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class HouseQrController extends Controller
{
    public function __construct(private readonly DeepLinks $links, private readonly QrPng $qr) {}

    public function show(Request $request, House $house): Response
    {
        $raw = $request->query('entrance');
        $entrance = is_string($raw) && $raw !== '' ? (int) $raw : null;
        abort_if($entrance !== null && ($entrance < 1 || $entrance > $house->entrances), 404);

        $name = 'qr-'.$house->qr_token.($entrance !== null ? '-'.$entrance : '').'.png';

        return response($this->qr->render($this->links->houseStartUrl($house, $entrance)), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="'.$name.'"',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
