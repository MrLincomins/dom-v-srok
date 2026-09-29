import { CellSimple } from '@maxhub/max-ui';
import type { RequestCard, RequestEvent } from '@/api/types';
import { texts } from '@/app/texts';
import { RequestSection } from '@/components/RequestSection';
import { eventComment } from '@/features/request/EventTimeline/EventTimeline.model';
import { describeRedirect } from '@/features/request/ResponsibleSection/ResponsibleSection.model';
import { withoutMarks } from '@/lib/address';
import { describeDeadline } from '@/lib/deadline';
import {
    deadlineRuns,
    statusExplain,
    statusTone,
    type StatusAudience,
    type StatusTone,
} from '@/lib/status';

const TONE_TEXT: Record<StatusTone, string> = {
    accepted: 'text-accepted-blue',
    progress: 'text-progress-blue',
    ready: 'text-ready-blue',
    done: 'text-done',
    late: 'text-late',
    muted: 'text-muted',
};

export function RequestSummary({ card, audience }: { card: RequestCard; audience: StatusAudience }) {
    const deadline = deadlineRuns(card.status) ? describeDeadline(card.deadline_fix_at) : null;
    const tone = statusTone(card.status, card.is_overdue);
    const redirected = card.status === 'redirected' ? describeRedirect(card.redirected_to) : null;
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
                subtitle={
                    deadline ? (
                        <span className={deadline.overdue ? 'deadline-overdue' : undefined}>{deadline.text}</span>
                    ) : undefined
                }
            />
            <CellSimple
                title={texts.request.what}
                subtitle={[card.category.name, card.description].filter(Boolean).join('. ')}
            />
            <CellSimple title={texts.request.where} subtitle={place} />
            {redirected && <CellSimple title={texts.request.redirected} subtitle={redirected} />}
            {card.returned_count > 0 && (
                <CellSimple
                    title={texts.request.returnedBadge}
                    subtitle={lastReturnComment(card.events) ?? undefined}
                />
            )}
            {card.participants_count > 0 && (
                <CellSimple
                    title={texts.request.neighbors}
                    subtitle={texts.request.joined(card.participants_count)}
                />
            )}
        </RequestSection>
    );
}

function lastReturnComment(events: RequestEvent[] | undefined): string | null {
    if (!events) return null;
    for (let index = events.length - 1; index >= 0; index -= 1) {
        const event = events[index];
        if (!event) continue;
        if (event.type !== 'returned' && event.to_status !== 'returned') continue;
        return eventComment(event);
    }
    return null;
}
