import { useState } from 'react';
import { CellList, CellSimple } from '@maxhub/max-ui';
import type { RequestCard } from '@/api/types';
import { texts } from '@/app/texts';
import { CellHeading } from '@/components/CellHeading';
import { formatDateTime } from '@/lib/dates';

export function ResponsibleSection({ card }: { card: RequestCard }) {
    const [open, setOpen] = useState(false);
    const responsible = `${card.responsible.name}${card.responsible.phone ? ` · ${card.responsible.phone}` : ''}`;
    const redirected = card.redirected_to
        ? [card.redirected_to.party?.name, card.redirected_to.party?.phone, card.redirected_to.note]
              .filter(Boolean)
              .join(' · ')
        : null;

    return (
        <div className="request-block flex min-w-0 flex-col">
            <CellHeading
                expanded={open}
                after={
                    <span className={`section-chevron${open ? ' is-open' : ''}`} aria-hidden>
                        <ChevronDown />
                    </span>
                }
                onClick={() => setOpen((value) => !value)}
            >
                {texts.request.execution}
            </CellHeading>
            <div className={`section-fold${open ? ' is-open' : ''}`} aria-hidden={!open}>
                <div className="section-fold-inner">
                    <div className="section-fold-body">
                        <CellList mode="island" filled className="request-sheet">
                            <CellSimple
                                title={texts.request.responsible}
                                subtitle={
                                    card.responsible.is_sure
                                        ? responsible
                                        : `${responsible}. ${texts.request.unsure}`
                                }
                            />
                            <CellSimple
                                title={texts.request.deadline}
                                subtitle={formatDateTime(card.deadline_fix_at)}
                            />
                            {card.deadline_reply_at && (
                                <CellSimple
                                    title={texts.request.replyDeadline}
                                    subtitle={formatDateTime(card.deadline_reply_at)}
                                />
                            )}
                            <CellSimple title={texts.request.basis} subtitle={card.basis || '—'} />
                            <CellSimple
                                title={texts.request.executor}
                                subtitle={card.executor?.name ?? texts.request.noExecutor}
                            />
                            {redirected && (
                                <CellSimple title={texts.request.redirected} subtitle={redirected} />
                            )}
                        </CellList>
                    </div>
                </div>
            </div>
        </div>
    );
}

function ChevronDown() {
    return (
        <svg width="12" height="8" viewBox="0 0 12 8" fill="none">
            <path
                d="M1.5 1.5 6 6l4.5-4.5"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
