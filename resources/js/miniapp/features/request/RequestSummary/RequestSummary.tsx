import { CellSimple } from '@maxhub/max-ui';
import type { RequestCard } from '@/api/types';
import { texts } from '@/app/texts';
import { RequestSection } from '@/components/RequestSection';
import { withoutMarks } from '@/lib/address';
import { describeDeadline } from '@/lib/deadline';
import { statusExplain, statusTone, type StatusAudience, type StatusTone } from '@/lib/status';

const TONE_TEXT: Record<StatusTone, string> = {
    accepted: 'text-accepted-blue',
    progress: 'text-progress-blue',
    ready: 'text-ready-blue',
    done: 'text-done',
    late: 'text-late',
    muted: 'text-muted',
};

export function RequestSummary({ card, audience }: { card: RequestCard; audience: StatusAudience }) {
    const closed = card.status === 'confirmed' || card.status === 'redirected';
    const deadline = closed ? null : describeDeadline(card.deadline_fix_at);
    const tone = statusTone(card.status, card.is_overdue);
    const place = [
        withoutMarks(card.house.address),
        card.entrance ? `подъезд ${card.entrance}` : null,
        card.flat ? `кв. ${card.flat}` : null,
    ]
        .filter(Boolean)
        .join(', ');

    return (
        <RequestSection>
            <CellSimple
                title={
                    <span className={TONE_TEXT[tone]}>
                        {statusExplain(card.status, card.is_overdue, audience)}
                    </span>
                }
                subtitle={deadline?.text}
            />
            <CellSimple
                title={texts.request.what}
                subtitle={[card.category.name, card.description].filter(Boolean).join('. ')}
            />
            <CellSimple title={texts.request.where} subtitle={place} />
            {card.returned_count > 0 && <CellSimple title={texts.request.returnedBadge} />}
            {card.participants_count > 0 && (
                <CellSimple
                    title={texts.request.neighbors}
                    subtitle={texts.request.joined(card.participants_count)}
                />
            )}
        </RequestSection>
    );
}
