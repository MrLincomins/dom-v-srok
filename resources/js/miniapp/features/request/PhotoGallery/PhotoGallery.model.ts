import type { Attachment, RequestEvent } from '@/api/types';
import { eventComment, photosWithUrl, type VisiblePhoto } from '@/features/request/EventTimeline/EventTimeline.model';

export type PhotoGroup = {
    kind: VisiblePhoto['kind'];
    photos: VisiblePhoto[];
    comment: string | null;
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
        });
    }

    const closing = photos.filter((photo) => photo.kind === 'closing');
    if (closing.length > 0) {
        groups.push({
            kind: 'closing',
            photos: closing,
            comment: commentFrom(items, (event) => event.to_status === 'done'),
        });
    }

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
