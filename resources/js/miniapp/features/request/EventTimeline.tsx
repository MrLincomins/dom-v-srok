import { CellHeader } from '@maxhub/max-ui';
import type { RequestEvent, RequestStatus } from '@/api/types';
import { texts } from '@/app/texts';
import { formatDateTime } from '@/lib/dates';
import { STATUS_LABEL } from '@/lib/status';

export function EventTimeline({ events }: { events: RequestEvent[] | undefined }) {
    const items = events ?? [];

    return (
        <section className="flex min-w-0 flex-col gap-12">
            <CellHeader titleStyle="caps">{texts.request.history}</CellHeader>
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
                                        className={`history-dot${tone === 'done' ? ' is-done' : ''}${tone === 'late' ? ' is-late' : ''}${last && tone === 'work' ? ' is-current' : ''}`}
                                    />
                                    {last ? null : (
                                        <span className="history-arrow">
                                            <span className="history-arrow-line" />
                                            <ArrowDown />
                                        </span>
                                    )}
                                </div>
                                <div className="history-body">
                                    <p
                                        className={`history-title${tone === 'done' ? ' text-done' : ''}${tone === 'late' ? ' text-late' : ''}`}
                                    >
                                        {eventLine(event)}
                                    </p>
                                    <p className="history-meta">
                                        {formatDateTime(event.created_at)}
                                        {event.actor_name ? ` · ${event.actor_name}` : ''}
                                    </p>
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

function eventTone(event: RequestEvent): 'done' | 'late' | 'work' {
    if (event.type === 'confirmed' || event.to_status === 'confirmed') {
        return 'done';
    }
    if (event.type === 'returned' || event.to_status === 'returned') {
        return 'late';
    }
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
