import { useState } from 'react';
import { useParams } from 'react-router';
import { Button, Textarea, Typography } from '@maxhub/max-ui';
import { useAuth } from '@/app/auth';
import { texts } from '@/app/texts';
import { DeadlineChip } from '@/components/DeadlineChip';
import { ErrorState, describe } from '@/components/ErrorState';
import { ListSkeleton } from '@/components/ListSkeleton';
import { PageHeader } from '@/components/PageHeader';
import { StatusChip } from '@/components/StatusChip';
import { formatDateTime } from '@/lib/dates';
import { NEXT_ACTIONS, STATUS_LABEL } from '@/lib/status';
import type { RequestCard, RequestEvent } from '@/api/types';
import { useRequest, useRequestActions } from './useRequest';

export function RequestPage() {
    const params = useParams();
    const id = Number(params.id);
    const { user } = useAuth();
    const request = useRequest(id);
    const backTo = user?.role === 'resident' ? '/my' : '/queue';

    if (request.isPending) {
        return (
            <main>
                <PageHeader title={texts.request.title(id)} backTo={backTo} />
                <ListSkeleton rows={4} />
            </main>
        );
    }
    if (request.isError) {
        return (
            <main>
                <PageHeader title={texts.request.title(id)} backTo={backTo} />
                <ErrorState error={request.error} onRetry={() => void request.refetch()} />
            </main>
        );
    }

    return (
        <main className="pb-8">
            <PageHeader title={texts.request.title(id)} backTo={backTo} />
            <Card card={request.data} isStaff={user?.role !== 'resident'} />
        </main>
    );
}

function Card({ card, isStaff }: { card: RequestCard; isStaff: boolean }) {
    const actions = useRequestActions(card.id);
    const [comment, setComment] = useState('');
    const [redirectName, setRedirectName] = useState('');
    const [redirectPhone, setRedirectPhone] = useState('');
    const [showRedirect, setShowRedirect] = useState(false);
    const busy =
        actions.status.isPending ||
        actions.redirect.isPending ||
        actions.comment.isPending ||
        actions.confirm.isPending;
    const mutationError =
        actions.status.error ?? actions.redirect.error ?? actions.comment.error ?? actions.confirm.error;
    const place = [
        card.house.address,
        card.entrance ? `подъезд ${card.entrance}` : null,
        card.flat ? `кв. ${card.flat}` : null,
    ]
        .filter(Boolean)
        .join(', ');
    const closed = card.status === 'confirmed' || card.status === 'redirected';

    return (
        <div className="flex flex-col gap-3 px-4">
            <section className="rounded-xl bg-surface p-4">
                <div className="mb-2 flex flex-wrap items-center gap-2">
                    <StatusChip status={card.status} overdue={card.is_overdue} />
                    <DeadlineChip deadline={card.deadline_fix_at} closed={closed} />
                    {card.status === 'done' && (
                        <span className="text-[13px] text-muted">{texts.request.awaitingResident}</span>
                    )}
                    {card.returned_count > 0 && (
                        <span className="text-[13px] text-late">{texts.request.returnedBadge}</span>
                    )}
                </div>
                <Typography.Title variant="medium-strong">{card.category.name}</Typography.Title>
                {card.description && (
                    <Typography.Body variant="medium" className="mt-1 block">
                        {card.description}
                    </Typography.Body>
                )}
                <Typography.Body variant="small" className="mt-2 block text-muted">
                    {place}
                </Typography.Body>
            </section>

            <section className="rounded-xl bg-surface p-4">
                <Row
                    label={texts.request.responsible}
                    value={
                        card.responsible.name + (card.responsible.phone ? `, ${card.responsible.phone}` : '')
                    }
                />
                {!card.responsible.is_sure && (
                    <Typography.Body variant="small" className="mb-2 block text-work">
                        {texts.request.unsure}
                    </Typography.Body>
                )}
                <Row label={texts.request.deadline} value={formatDateTime(card.deadline_fix_at)} />
                {card.deadline_reply_at && (
                    <Row label={texts.request.replyDeadline} value={formatDateTime(card.deadline_reply_at)} />
                )}
                <Row label={texts.request.basis} value={card.basis || '—'} />
                <Row label={texts.request.executor} value={card.executor?.name ?? texts.request.noExecutor} />
                {card.redirected_to && (
                    <Row
                        label={STATUS_LABEL.redirected}
                        value={[
                            card.redirected_to.party?.name,
                            card.redirected_to.party?.phone,
                            card.redirected_to.note,
                        ]
                            .filter(Boolean)
                            .join(', ')}
                    />
                )}
            </section>

            {card.attachments && card.attachments.length > 0 && (
                <section className="flex gap-2 overflow-x-auto">
                    {card.attachments.map((a) =>
                        a.url ? (
                            <img
                                key={a.id}
                                src={a.url}
                                alt={a.kind === 'closing' ? 'Фото результата' : 'Фото жителя'}
                                className="h-28 w-28 shrink-0 rounded-xl object-cover"
                            />
                        ) : null,
                    )}
                </section>
            )}

            {isStaff && !closed && (
                <section className="flex flex-col gap-2 rounded-xl bg-surface p-4">
                    <div className="flex flex-wrap gap-2">
                        {NEXT_ACTIONS[card.status].map((action) => (
                            <Button
                                key={action.status}
                                size="medium"
                                variant={action.status === 'done' ? 'primary' : 'secondary'}
                                loading={actions.status.isPending}
                                disabled={busy}
                                onClick={() =>
                                    actions.status.mutate({
                                        status: action.status,
                                        comment: comment || undefined,
                                    })
                                }
                            >
                                {action.label}
                            </Button>
                        ))}
                        {card.status !== 'done' && (
                            <Button
                                size="medium"
                                variant="ghost"
                                disabled={busy}
                                onClick={() => setShowRedirect((v) => !v)}
                            >
                                {texts.request.actions.redirect}
                            </Button>
                        )}
                    </div>

                    {showRedirect && (
                        <form
                            className="flex flex-col gap-2"
                            onSubmit={(e) => {
                                e.preventDefault();
                                actions.redirect.mutate({
                                    name: redirectName.trim(),
                                    phone: redirectPhone.trim() || undefined,
                                    note: comment || undefined,
                                });
                            }}
                        >
                            <input
                                className="rounded-lg border border-muted/30 bg-page px-3 py-2 text-[16px]"
                                placeholder="Кому: организация или служба"
                                value={redirectName}
                                onChange={(e) => setRedirectName(e.target.value)}
                                required
                            />
                            <input
                                className="rounded-lg border border-muted/30 bg-page px-3 py-2 text-[16px]"
                                placeholder="Телефон (необязательно)"
                                value={redirectPhone}
                                onChange={(e) => setRedirectPhone(e.target.value)}
                                inputMode="tel"
                            />
                            <Button
                                type="submit"
                                size="medium"
                                variant="destructive"
                                loading={actions.redirect.isPending}
                                disabled={busy}
                            >
                                {texts.request.actions.redirect}
                            </Button>
                        </form>
                    )}

                    <Textarea
                        placeholder={texts.request.commentPlaceholder}
                        value={comment}
                        onChange={(e) => setComment(e.target.value)}
                        rows={2}
                    />
                    <Button
                        size="small"
                        variant="ghost"
                        disabled={busy || comment.trim() === ''}
                        loading={actions.comment.isPending}
                        onClick={() =>
                            actions.comment.mutate(comment.trim(), { onSuccess: () => setComment('') })
                        }
                    >
                        {texts.request.actions.comment}
                    </Button>
                </section>
            )}

            {!isStaff && card.status === 'done' && (
                <section className="flex gap-2 rounded-xl bg-surface p-4">
                    <Button
                        size="large"
                        variant="primary"
                        stretched
                        loading={actions.confirm.isPending}
                        disabled={busy}
                        onClick={() => actions.confirm.mutate({ resolved: true })}
                    >
                        Да, решено
                    </Button>
                    <Button
                        size="large"
                        variant="secondary"
                        stretched
                        disabled={busy}
                        onClick={() => actions.confirm.mutate({ resolved: false })}
                    >
                        Нет, не решено
                    </Button>
                </section>
            )}

            {mutationError && (
                <Typography.Body variant="small" className="text-negative" role="alert">
                    {describe(mutationError)}
                </Typography.Body>
            )}

            <section className="rounded-xl bg-surface p-4">
                <Typography.Title variant="small-strong" className="mb-2 block">
                    {texts.request.history}
                </Typography.Title>
                <ol className="flex flex-col gap-2">
                    {(card.events ?? []).map((event) => (
                        <li key={event.id} className="flex flex-col">
                            <span className="text-[15px]">{eventLine(event)}</span>
                            <span className="text-[13px] text-muted">
                                {formatDateTime(event.created_at)}
                                {event.actor_name ? ` · ${event.actor_name}` : ''}
                            </span>
                        </li>
                    ))}
                </ol>
            </section>
        </div>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="mb-2 flex flex-col">
            <span className="text-[13px] text-muted">{label}</span>
            <span className="text-[16px]">{value}</span>
        </div>
    );
}

function eventLine(event: RequestEvent): string {
    const comment = event.comment ? ` — ${event.comment}` : '';
    switch (event.type) {
        case 'created':
            return `Заявка принята${comment}`;
        case 'assigned':
            return `Назначен исполнитель: ${String(event.payload?.executor ?? '')}${comment}`;
        case 'status_changed':
            return `${event.to_status ? (STATUS_LABEL[event.to_status as keyof typeof STATUS_LABEL] ?? event.to_status) : ''}${comment}`;
        case 'comment':
            return `Комментарий${comment}`;
        case 'participant_joined':
            return 'Присоединился сосед';
        default:
            return `${event.type}${comment}`;
    }
}
