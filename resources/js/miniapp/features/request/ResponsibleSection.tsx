import { CellSimple } from '@maxhub/max-ui';
import type { RequestCard } from '@/api/types';
import { texts } from '@/app/texts';
import { Section } from '@/components/Section';
import { formatDateTime } from '@/lib/dates';

export function ResponsibleSection({ card }: { card: RequestCard }) {
    const responsible = `${card.responsible.name}${card.responsible.phone ? ` · ${card.responsible.phone}` : ''}`;
    const redirected = card.redirected_to
        ? [card.redirected_to.party?.name, card.redirected_to.party?.phone, card.redirected_to.note]
              .filter(Boolean)
              .join(' · ')
        : null;

    return (
        <Section title={texts.request.execution}>
            <CellSimple
                title={texts.request.responsible}
                subtitle={card.responsible.is_sure ? responsible : `${responsible}. ${texts.request.unsure}`}
            />
            <CellSimple title={texts.request.deadline} subtitle={formatDateTime(card.deadline_fix_at)} />
            {card.deadline_reply_at && (
                <CellSimple title={texts.request.replyDeadline} subtitle={formatDateTime(card.deadline_reply_at)} />
            )}
            <CellSimple title={texts.request.basis} subtitle={card.basis || '—'} />
            <CellSimple title={texts.request.executor} subtitle={card.executor?.name ?? texts.request.noExecutor} />
            {redirected && <CellSimple title={texts.request.redirected} subtitle={redirected} />}
        </Section>
    );
}
