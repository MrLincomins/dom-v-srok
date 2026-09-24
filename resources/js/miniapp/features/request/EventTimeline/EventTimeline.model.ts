import type { Attachment, RequestEvent, RequestStatus } from '@/api/types';
import { texts } from '@/app/texts';
import { STATUS_LABEL, type StatusTone } from '@/lib/status';

export type VisiblePhoto = Attachment & { url: string };

export function photosWithUrl(attachments: Attachment[] | undefined): VisiblePhoto[] {
    return (
        attachments?.filter(
            (attachment): attachment is VisiblePhoto =>
                typeof attachment.url === 'string' && attachment.url.length > 0,
        ) ?? []
    );
}

export function eventPhotos(
    event: RequestEvent,
    attachments: Attachment[] | undefined,
    events: RequestEvent[] = [],
): VisiblePhoto[] {
    const photos = photosWithUrl(attachments);
    if (photos.length === 0) return [];
    if (event.type === 'photo_added') {
        const id = event.payload?.attachment_id;
        return typeof id === 'number' ? photos.filter((photo) => photo.id === id) : [];
    }
    if (event.to_status === 'done') {
        const timeline = events.length > 0 ? events : [event];
        return closingPhotosByDoneEvent(timeline, photos).get(event.id) ?? [];
    }
    if (event.type === 'created') return photos.filter((photo) => photo.kind === 'resident');
    return [];
}

/** Фото закрытия — к тому «Выполнено», после которого их загрузили, а не ко всем сразу. */
export function closingPhotosByDoneEvent(
    events: RequestEvent[],
    photos: VisiblePhoto[],
): Map<number, VisiblePhoto[]> {
    const done = events
        .filter((event) => event.to_status === 'done')
        .slice()
        .sort((left, right) => compareStamp(left, right));
    const result = new Map<number, VisiblePhoto[]>(done.map((event) => [event.id, []]));
    if (done.length === 0) return result;

    const closing = photos
        .filter((photo) => photo.kind === 'closing')
        .slice()
        .sort((left, right) => compareStamp(left, right));

    for (const photo of closing) {
        const photoTime = stamp(photo);
        let owner = done[done.length - 1];
        for (const event of done) {
            if (photoTime <= stamp(event)) {
                owner = event;
                break;
            }
        }
        result.get(owner.id)?.push(photo);
    }

    return result;
}

function stamp(item: { created_at: string }): number {
    const time = Date.parse(item.created_at);
    return Number.isNaN(time) ? 0 : time;
}

function compareStamp(left: { created_at: string; id: number }, right: { created_at: string; id: number }): number {
    return stamp(left) - stamp(right) || left.id - right.id;
}

export function visibleEvents(events: RequestEvent[]): RequestEvent[] {
    const hasAssigned = events.some((event) => event.type === 'assigned');
    if (!hasAssigned) return events;
    return events.filter((event) => !(event.type === 'status_changed' && event.to_status === 'assigned'));
}

export function eventTone(event: RequestEvent): StatusTone {
    if (event.type === 'confirmed' || event.to_status === 'confirmed') return 'done';
    if (event.type === 'redirected' || event.to_status === 'redirected') return 'muted';
    if (event.to_status === 'done') return 'ready';
    if (event.to_status === 'in_progress' || event.type === 'returned') return 'progress';
    return 'accepted';
}

export function eventComment(event: RequestEvent): string | null {
    const text = event.comment?.trim();
    return text ? text : null;
}

export function eventLine(event: RequestEvent): string {
    switch (event.type) {
        case 'created':
            return texts.request.events.created;
        case 'assigned':
            return `${texts.request.events.assigned}${payloadExecutor(event)}`;
        case 'status_changed':
            return statusLabel(event.to_status);
        case 'redirected':
            return texts.request.events.redirected;
        case 'returned':
            return texts.request.events.returned;
        case 'confirmed':
            return texts.request.events.confirmed;
        case 'comment':
            return texts.request.events.comment;
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
