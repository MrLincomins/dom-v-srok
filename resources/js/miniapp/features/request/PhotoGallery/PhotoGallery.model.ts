import type { Attachment, RequestEvent } from '@/api/types';
import { texts } from '@/app/texts';
import {
    closingPhotosByDoneEvent,
    eventComment,
    photosWithUrl,
    type VisiblePhoto,
} from '@/features/request/EventTimeline/EventTimeline.model';

export type PhotoGroup = {
    kind: VisiblePhoto['kind'];
    photos: VisiblePhoto[];
    comment: string | null;
    label: string;
};

export function photoGroups(
    attachments: Attachment[] | undefined,
    events: RequestEvent[] | undefined,
    description?: string,
): PhotoGroup[] {
    const photos = photosWithUrl(attachments);
    const items = events ?? [];
    const groups: PhotoGroup[] = [];

    const resident = photos.filter((photo) => photo.kind === 'resident');
    if (resident.length > 0) {
        groups.push({
            kind: 'resident',
            photos: resident,
            comment:
                commentFrom(items, (event) => event.type === 'created') ??
                description?.trim() ??
                null,
            label: texts.request.residentPhoto,
        });
    }

    const closing = photos.filter((photo) => photo.kind === 'closing');
    const byDone = closingPhotosByDoneEvent(items, photos);
    const doneEvents = items.filter((event) => event.to_status === 'done');
    const closingGroups: PhotoGroup[] = [];
    if (doneEvents.length === 0 && closing.length > 0) {
        closingGroups.push({
            kind: 'closing',
            photos: closing,
            comment: null,
            label: texts.request.closingPhoto,
        });
    }
    for (const event of doneEvents) {
        const eventPhotos = byDone.get(event.id) ?? [];
        if (eventPhotos.length === 0) continue;
        closingGroups.push({
            kind: 'closing',
            photos: eventPhotos,
            comment: eventComment(event),
            label: texts.request.closingPhoto,
        });
    }
    const many = closingGroups.length > 1;
    closingGroups.forEach((group, index) => {
        groups.push({
            ...group,
            label: many ? texts.request.closingRound(index + 1) : group.label,
        });
    });

    return groups;
}

function commentFrom(events: RequestEvent[], match: (event: RequestEvent) => boolean): string | null {
    for (let index = events.length - 1; index >= 0; index -= 1) {
        const event = events[index];
        if (!match(event)) continue;
        const text = eventComment(event);
        if (text) return text;
    }
    return null;
}
