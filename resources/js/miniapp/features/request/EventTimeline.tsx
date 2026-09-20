import type { RequestEvent, RequestStatus } from '@/api/types';
import { texts } from '@/app/texts';
import { formatDateTime } from '@/lib/dates';
import { STATUS_LABEL, type StatusTone } from '@/lib/status';

const TONE_TEXT: Record<StatusTone, string> = {
    fresh: ' text-fresh',
    accepted: ' text-accepted-blue',
    work: ' text-work',
    done: ' text-done',
    late: ' text-late',
    muted: ' text-muted',
};

const TONE_DOT: Record<StatusTone, string> = {
    fresh: ' is-fresh',
    accepted: ' is-current',
    work: ' is-current',
    done: ' is-done',
    late: ' is-late',
    muted: '',
};

export function EventTimeline({ events }: { events: RequestEvent[] | undefined }) {
    const items = events ?? [];

    return (
        <section className="request-block flex min-w-0 flex-col gap-8">
            <h3 className="request-block-title">{texts.request.history}</h3>
            <div className="history-card">
                {items.length === 0 ? (
                    <p className="history-empty">{texts.request.historyEmpty}</p>
                ) : (
                    items.map((event, index) => {
                        const last = index === items.length - 1;
                        const tone = eventTone(event);
                        return (
                            <div key={event.id} className="history-step">
                                <div className="history-rail" aria-hidden>
                                    <span
                                        className={`history-dot${last ? TONE_DOT[tone] : ''}`}
                                    />
                                    {last ? null : (
                                        <span className="history-arrow">
                                            <span className="history-arrow-line" />
                                            <ArrowDown />
                                        </span>
                                    )}
                                </div>
                                <div className="history-body">
                                    <p className={`history-title${TONE_TEXT[tone]}`}>{eventLine(event)}</p>
                                    <p className="history-meta">{formatDateTime(event.created_at)}</p>
                                </div>
                            </div>
                        );
                    })
                )}
            </div>
        </section>
    );
}

function ArrowDown() {
    return (
        <svg width="12" height="10" viewBox="0 0 12 10" fill="none">
            <path
                d="M2 3.5 6 7.5 10 3.5"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

function eventTone(event: RequestEvent): StatusTone {
    if (event.type === 'confirmed' || event.to_status === 'confirmed') return 'done';
    if (event.type === 'returned' || event.to_status === 'returned') return 'late';
    if (event.type === 'redirected' || event.to_status === 'redirected') return 'muted';
    return 'work';
}
function eventLine(event: RequestEvent): string {
    const comment = event.comment ? ` — ${event.comment}` : '';
    switch (event.type) {
        case 'created':
            return `${texts.request.events.created}${comment}`;
        case 'assigned':
            return `${texts.request.events.assigned}${payloadExecutor(event)}${comment}`;
        case 'status_changed':
            return `${statusLabel(event.to_status)}${comment}`;
        case 'redirected':
            return `${texts.request.events.redirected}${comment}`;
        case 'returned':
            return `${texts.request.events.returned}${comment}`;
        case 'confirmed':
            return `${texts.request.events.confirmed}${comment}`;
        case 'comment':
            return `${texts.request.events.comment}${comment}`;
        case 'photo_added':
            return texts.request.events.photoAdded;
        case 'participant_joined':
            return texts.request.events.participantJoined;
        case 'notification':
            return texts.request.events.notification;
        case 'reminder':
            return texts.request.events.reminder;
    }
}

function statusLabel(status: string | null | undefined): string {
    if (!status) return texts.request.events.statusChanged;
    return isRequestStatus(status) ? STATUS_LABEL[status] : status;
}

function isRequestStatus(value: string): value is RequestStatus {
    return value in STATUS_LABEL;
}

function payloadExecutor(event: RequestEvent): string {
    const executor = event.payload?.executor;
    return typeof executor === 'string' && executor ? `: ${executor}` : '';
}
