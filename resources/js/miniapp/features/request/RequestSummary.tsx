import { Typography } from '@maxhub/max-ui';
import type { RequestCard } from '@/api/types';
import { DeadlineChip } from '@/components/DeadlineChip';
import { StatusChip } from '@/components/StatusChip';
import { texts } from '@/app/texts';

export function RequestSummary({ card }: { card: RequestCard }) {
    const closed = card.status === 'confirmed' || card.status === 'redirected';
    const place = [
        card.house.address,
        card.entrance ? `подъезд ${card.entrance}` : null,
        card.flat ? `кв. ${card.flat}` : null,
    ]
        .filter(Boolean)
        .join(', ');

    return (
        <section className="app-card enter p-16">
            <div className="mb-12 flex flex-wrap items-center gap-8">
                <StatusChip status={card.status} overdue={card.is_overdue} label={card.status_label} />
                <DeadlineChip deadline={card.deadline_fix_at} closed={closed} />
                {card.status === 'done' && (
                    <span className="text-[13px] text-muted">{texts.request.awaitingResident}</span>
                )}
                {card.returned_count > 0 && (
                    <span className="text-[13px] font-medium text-late">{texts.request.returnedBadge}</span>
                )}
            </div>
            <Typography.Title variant="medium-strong">{card.category.name}</Typography.Title>
            {card.description && (
                <Typography.Body variant="medium" className="mt-4 block leading-relaxed">
                    {card.description}
                </Typography.Body>
            )}
            <Typography.Body variant="small" className="mt-12 block text-muted">
                {place}
            </Typography.Body>
            {card.participants_count > 0 && (
                <Typography.Body variant="small" className="mt-4 block text-muted">
                    {texts.request.joined(card.participants_count)}
                </Typography.Body>
            )}
        </section>
    );
}
