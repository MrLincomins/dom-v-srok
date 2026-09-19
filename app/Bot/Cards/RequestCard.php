<?php

declare(strict_types=1);

namespace App\Bot\Cards;

use App\Bot\Fsm\ReportDraft;
use App\Bot\Texts\TextRepository;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Responsible;
use App\Domain\Organizations\Models\House;
use App\Domain\Requests\Models\ServiceRequest;
use Carbon\CarbonImmutable;

/** один формат карточки для бота: жителю с подъездом, в чат дома только категория */
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

    public function chatStatus(ServiceRequest $request): string
    {
        $request->loadMissing(['category', 'house.region']);
        $text = $this->texts->text('chat.status', [
            'number' => $request->id,
            'status' => $request->status->label(),
            'what' => $this->what($request, true),
            'deadline' => $this->deadline($request),
        ]);
        if ($request->participants_count > 0) {
            $text .= "\n".$this->texts->text('chat.neighbours', ['count' => $request->participants_count]);
        }

        return $text;
    }

    public function chatOffer(ServiceRequest $request): string
    {
        $request->loadMissing(['category', 'house.region']);

        return $this->texts->text('chat.join_offer', [
            'number' => $request->id,
            'what' => $this->what($request, true),
            'deadline' => $this->deadline($request),
        ]);
    }

    public function preview(ReportDraft $draft, Category $category, House $house, Responsible $responsible, ?CarbonImmutable $fix, ?CarbonImmutable $reply): string
    {
        $what = $category->name;
        if ($draft->description !== '') {
            $what .= ': '.mb_strimwidth($draft->description, 0, 120, '…');
        }
        if ($draft->entrance !== null) {
            $what .= ', подъезд '.$draft->entrance;
        }
        $hint = (string) $responsible->hint;

        return trim($this->texts->text('report.preview_card', [
            'what' => $what,
            'responsible' => $responsible->name.($responsible->phone !== null ? ', '.$responsible->phone : ''),
            'deadline' => $this->deadlineText($fix, $reply, $house->region->timezone),
            'basis' => $category->basis ?? '—',
            'hint' => $responsible->isSure ? $hint : trim('Скорее всего. '.$hint),
        ]));
    }

    public function what(ServiceRequest $request, bool $forChat): string
    {
        if ($forChat) {
            return $request->category->name;
        }
        $what = $request->category->name;
        if ($request->description !== '') {
            $what .= ': '.mb_strimwidth($request->description, 0, 120, '…');
        }
        if ($request->entrance !== null) {
            $what .= ', подъезд '.$request->entrance;
        }

        return $what;
    }

    public function deadline(ServiceRequest $request): string
    {
        return $this->deadlineText($request->deadline_fix_at, $request->deadline_reply_at, $request->house->region->timezone);
    }

    public function deadlineText(?CarbonImmutable $fix, ?CarbonImmutable $reply, string $timezone): string
    {
        if ($fix !== null) {
            return 'до '.$this->humanDate($fix, $timezone);
        }
        if ($reply !== null) {
            return 'ответ до '.$this->humanDate($reply, $timezone).' (срок ответа по закону)';
        }

        return 'по договору';
    }

    private function humanDate(CarbonImmutable $at, string $timezone): string
    {
        $local = $at->setTimezone($timezone)->locale('ru');

        return $local->isoFormat('dddd, D MMMM, HH:mm');
    }
}
