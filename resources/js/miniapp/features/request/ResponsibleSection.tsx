import { Typography } from '@maxhub/max-ui';
import type { RequestCard } from '@/api/types';
import { texts } from '@/app/texts';
import { formatDateTime } from '@/lib/dates';

export function ResponsibleSection({ card }: { card: RequestCard }) {
    return (
        <section className="app-card enter p-16">
            <Typography.Title variant="small-strong" className="mb-12 block">
                {texts.request.execution}
            </Typography.Title>
            <Row
                label={texts.request.responsible}
                value={`${card.responsible.name}${card.responsible.phone ? ` · ${card.responsible.phone}` : ''}`}
            />
            {!card.responsible.is_sure && (
                <Typography.Body variant="small" className="mb-12 block text-work">
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
                    label={texts.request.redirected}
                    value={[
                        card.redirected_to.party?.name,
                        card.redirected_to.party?.phone,
                        card.redirected_to.note,
                    ]
                        .filter(Boolean)
                        .join(' · ')}
                />
            )}
        </section>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="mb-12 last:mb-0">
            <span className="mb-2 block text-[13px] text-muted">{label}</span>
            <span className="block text-[16px] leading-snug">{value}</span>
        </div>
    );
}
