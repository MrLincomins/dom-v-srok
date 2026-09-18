import { Typography } from '@maxhub/max-ui';
import type { RequestEvent, RequestStatus } from '@/api/types';
import { texts } from '@/app/texts';
import { formatDateTime } from '@/lib/dates';
import { STATUS_LABEL } from '@/lib/status';

export function EventTimeline({ events }: { events: RequestEvent[] | undefined }) {
    return (
        <section className="app-card enter p-16">
            <Typography.Title variant="small-strong" className="mb-16 block">
                {texts.request.history}
            </Typography.Title>
            {!events?.length ? (
                <Typography.Body variant="small" className="text-muted">
                    {texts.request.historyEmpty}
                </Typography.Body>
            ) : (
                <ol className="flex flex-col">
                    {events.map((event, index) => (
                        <li key={event.id} className="relative flex gap-12 pb-16 last:pb-0">
                            <span className="mt-6 h-8 w-8 shrink-0 rounded-full bg-accent" />
                            {index < events.length - 1 && (
                                <span className="absolute top-16 bottom-0 left-[3px] w-px bg-divider" />
                            )}
                            <span className="min-w-0">
                                <span className="block text-[15px] leading-snug">{eventLine(event)}</span>
                                <span className="mt-2 block text-[13px] text-muted">
                                    {formatDateTime(event.created_at)}
                                    {event.actor_name ? ` · ${event.actor_name}` : ''}
                                </span>
                            </span>
                        </li>
                    ))}
                </ol>
            )}
        </section>
    );
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
