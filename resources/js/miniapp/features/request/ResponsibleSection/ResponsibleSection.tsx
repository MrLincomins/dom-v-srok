import { useState } from 'react';
import { CellList, CellSimple } from '@maxhub/max-ui';
import type { RequestCard } from '@/api/types';
import { texts } from '@/app/texts';
import { formatDateTime } from '@/lib/dates';
import { describeRedirect } from './ResponsibleSection.model';

export function ResponsibleSection({ card }: { card: RequestCard }) {
    const [open, setOpen] = useState(false);
    const responsible = `${card.responsible.name}${card.responsible.phone ? ` · ${card.responsible.phone}` : ''}`;
    const redirected = describeRedirect(card.redirected_to);
    const preview = [card.responsible.name, formatDateTime(card.deadline_fix_at)].filter(Boolean).join(' · ');

    return (
        <div className="request-block flex min-w-0 flex-col">
            <div className={`section-disclosure-card${open ? ' is-open' : ''}`}>
                <button
                    type="button"
                    className="section-disclosure"
                    aria-expanded={open}
                    aria-label={texts.request.execution}
                    onClick={() => setOpen((value) => !value)}
                >
                    <span className="section-disclosure-copy">
                        <span className="section-disclosure-title">{texts.request.execution}</span>
                        {!open && <span className="section-disclosure-hint">{preview}</span>}
                    </span>
                    <span className="section-disclosure-action" aria-hidden>
                        {open ? texts.request.hideDetails : texts.request.showDetails}
                        <span className={`section-chevron${open ? ' is-open' : ''}`}>
                            <ChevronDown />
                        </span>
                    </span>
                </button>
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
        </div>
    );
}

function ChevronDown() {
    return (
        <svg width="16" height="10" viewBox="0 0 16 10" fill="none">
            <path
                d="M2 2.5 8 8l6-5.5"
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
