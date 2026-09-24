import type { Attachment, RequestEvent } from '@/api/types';
import { texts } from '@/app/texts';
import { CellHeading } from '@/components/CellHeading';
import { RequestPhoto } from '@/features/request/PhotoGallery';
import { formatDateTime } from '@/lib/dates';
import type { StatusTone } from '@/lib/status';
import { eventComment, eventLine, eventPhotos, eventTone, visibleEvents } from './EventTimeline.model';

const TONE_CLASS: Record<StatusTone, string> = {
    accepted: ' is-accepted',
    progress: ' is-progress',
    ready: ' is-ready',
    done: ' is-done',
    late: ' is-late',
    muted: ' is-muted',
};

export function EventTimeline({
    events,
    attachments,
}: {
    events: RequestEvent[] | undefined;
    attachments?: Attachment[];
}) {
    const items = visibleEvents(events ?? []);

    return (
        <section className="request-block flex min-w-0 flex-col gap-8">
            <CellHeading>{texts.request.history}</CellHeading>
            <div className="history-card">
                {items.length === 0 ? (
                    <p className="history-empty">{texts.request.historyEmpty}</p>
                ) : (
                    items.map((event, index) => {
                        const last = index === items.length - 1;
                        const tone = TONE_CLASS[eventTone(event)];
                        const comment = eventComment(event);
                        const photos = eventPhotos(event, attachments);
                        return (
                            <div key={event.id} className="history-step">
                                <div className="history-rail" aria-hidden>
                                    <span className={`history-dot${tone}`} />
                                    {last ? null : (
                                        <span className="history-arrow">
                                            <span className="history-arrow-line" />
                                            <ArrowDown />
                                        </span>
                                    )}
                                </div>
                                <div className="history-body">
                                    <p className={`history-title${tone}`}>{eventLine(event)}</p>
                                    {comment ? <p className="history-comment">{comment}</p> : null}
                                    {photos.length > 0 ? (
                                        <div className="history-photos">
                                            {photos.map((photo, photoIndex) => (
                                                <RequestPhoto
                                                    key={photo.id}
                                                    url={photo.url}
                                                    alt={
                                                        photo.kind === 'closing'
                                                            ? `${texts.request.closingPhoto} ${photoIndex + 1}`
                                                            : `${texts.request.residentPhoto} ${photoIndex + 1}`
                                                    }
                                                />
                                            ))}
                                        </div>
                                    ) : null}
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
