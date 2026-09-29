<?php

declare(strict_types=1);

namespace App\Domain\Requests;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Requests\Dto\JournalPeriod;
use App\Domain\Requests\Enums\ConfirmedBy;
use App\Domain\Requests\Enums\RequestStatus;
use App\Domain\Requests\Models\ServiceRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use League\Csv\Bom;
use League\Csv\EscapeFormula;
use League\Csv\Writer;

final class JournalExporter
{
    private const COLUMNS = [
        '№ заявки', 'Дата и время приёма', 'Адрес', 'Подъезд', 'Квартира', 'Заявитель', 'Категория', 'Описание',
        'Кто отвечает', 'Телефон ответственного', 'Срок по нормативу', 'Основание', 'Статус', 'Исполнитель',
        'Первое действие диспетчера', 'Выполнено', 'Закрыто', 'В срок', 'Подтверждение', 'Возвратов',
        'Кому передано', 'Соседей присоединилось',
    ];

    /** @return Builder<ServiceRequest> */
    public function rows(Organization $organization, JournalPeriod $period): Builder
    {
        return $this->base($organization, $period)
            ->with([
                'category', 'house', 'resident', 'executor', 'redirectedParty',
                'events' => fn ($events) => $events->where('to_status', RequestStatus::Redirected->value),
            ])
            ->orderBy('id');
    }

    /** @return array{total:int,open:int,overdue:int,closed:int,on_time:int,late:int,returned:int,redirected:int,first_reaction_minutes:int|null} */
    public function summary(Organization $organization, JournalPeriod $period): array
    {
        $base = fn (): Builder => $this->base($organization, $period);
        $minutes = $base()->whereNotNull('first_reaction_at')
            ->selectRaw('avg(extract(epoch from (first_reaction_at - created_at)) / 60) as minutes')
            ->value('minutes');

        return [
            'total' => $base()->count(),
            'open' => $base()->open()->count(),
            'overdue' => $base()->overdue()->count(),
            'closed' => $base()->closed()->count(),
            'on_time' => $base()->whereNotNull('done_at')
                ->where(fn (Builder $q) => $q->whereNull('deadline_fix_at')->orWhereColumn('done_at', '<=', 'deadline_fix_at'))
                ->count(),
            'late' => $base()->whereNotNull('done_at')->whereColumn('done_at', '>', 'deadline_fix_at')->count(),
            'returned' => $base()->where('returned_count', '>', 0)->count(),
            'redirected' => $base()->where('status', RequestStatus::Redirected->value)->count(),
            'first_reaction_minutes' => $minutes === null ? null : (int) round((float) $minutes),
        ];
    }

    public function csv(Organization $organization, JournalPeriod $period): string
    {
        $timezone = $organization->region->timezone;
        $writer = Writer::createFromString();
        $writer->setDelimiter(';');
        $writer->setOutputBOM(Bom::Utf8);
        $writer->addFormatter(new EscapeFormula);
        $writer->insertOne(self::COLUMNS);
        foreach ($this->rows($organization, $period)->lazyById(200) as $request) {
            $writer->insertOne($this->line($request, $timezone));
        }

        return $writer->toString();
    }

    /** @return Builder<ServiceRequest> */
    private function base(Organization $organization, JournalPeriod $period): Builder
    {
        [$from, $to] = $period->bounds();

        return ServiceRequest::query()->forOrganization($organization->id)->whereBetween('created_at', [$from, $to]);
    }

    /** @return list<string> */
    private function line(ServiceRequest $request, string $timezone): array
    {
        $at = fn (?CarbonImmutable $moment): string => $moment?->setTimezone($timezone)->format('d.m.Y H:i') ?? '';

        return [
            (string) $request->id,
            $at($request->created_at),
            $request->house->address,
            (string) ($request->entrance ?? ''),
            (string) ($request->flat ?? ''),
            $request->resident->name,
            $request->category->name,
            $request->description,
            $request->responsible_name,
            (string) ($request->responsible_phone ?? ''),
            $at($request->deadline_fix_at),
            $request->basis,
            $request->status->label(),
            $request->executor->name ?? '',
            $at($request->first_reaction_at),
            $at($request->done_at),
            $at($request->closed_at),
            $this->onTime($request),
            $this->confirmation($request),
            (string) $request->returned_count,
            $request->redirectedToName() ?? '',
            (string) $request->participants_count,
        ];
    }

    private function onTime(ServiceRequest $request): string
    {
        if ($request->done_at === null) {
            return $request->isOverdue() ? 'просрочена' : '';
        }

        return $request->closedLate() ? 'нет' : 'да';
    }

    private function confirmation(ServiceRequest $request): string
    {
        return match ($request->confirmed_by) {
            ConfirmedBy::Resident => 'житель',
            ConfirmedBy::Auto => 'автоматически',
            ConfirmedBy::Dispatcher => 'диспетчер',
            null => '',
        };
    }
}
