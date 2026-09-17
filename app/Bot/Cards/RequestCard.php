<?php

declare(strict_types=1);

namespace App\Bot\Cards;

use App\Bot\Texts\TextRepository;
use App\Domain\Requests\Models\ServiceRequest;
use Carbon\CarbonImmutable;

/** формат для заявок, для кв без номеров квартир*/
final class RequestCard
{
    public function __construct(private readonly TextRepository $texts) {}

    public function created(ServiceRequest $request): string
    {
        $request->loadMissing(['category', 'house.region']);

        return $this->texts->text('request.created', [
            'number' => $request->id,
            'what' => $this->what($request, false),
            'responsible' => $request->responsible_name.($request->responsible_phone ? ', '.$request->responsible_phone : ''),
            'deadline' => $this->deadline($request),
            'basis' => $request->basis !== '' ? $request->basis : '—',
            'next' => $this->texts->text($request->is_sure ? 'request.created.next' : 'request.created.unsure'),
        ]);
    }

    public function what(ServiceRequest $request, bool $forChat): string
    {
        $what = $request->category->name;
        if ($request->description !== '') {
            $what .= ': '.mb_strimwidth($request->description, 0, 120, '…');
        }
        if (! $forChat && $request->entrance !== null) {
            $what .= ', подъезд '.$request->entrance;
        }

        return $what;
    }

    public function deadline(ServiceRequest $request): string
    {
        $timezone = $request->house->region->timezone;

        if ($request->deadline_fix_at !== null) {
            return 'до '.$this->humanDate($request->deadline_fix_at, $timezone);
        }
        if ($request->deadline_reply_at !== null) {
            return 'ответ до '.$this->humanDate($request->deadline_reply_at, $timezone).' (срок ответа по закону)';
        }

        return 'по договору';
    }

    private function humanDate(CarbonImmutable $at, string $timezone): string
    {
        $local = $at->setTimezone($timezone)->locale('ru');

        return $local->isoFormat('dddd, D MMMM, HH:mm');
    }
}
