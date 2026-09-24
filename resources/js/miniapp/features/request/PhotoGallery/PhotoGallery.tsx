import type { Attachment, RequestEvent } from '@/api/types';
import { texts } from '@/app/texts';
import { CellHeading } from '@/components/CellHeading';
import { PhotoGrid } from '@/components/PhotoGrid';
import { openExternalLink } from '@/bridge/maxWebApp';
import { photoGroups } from './PhotoGallery.model';

export function RequestPhoto({ url, alt }: { url: string; alt: string }) {
    return (
        <button type="button" className="request-photo-btn" onClick={() => openExternalLink(url)}>
            <img src={url} alt={alt} className="request-photo" loading="lazy" decoding="async" />
        </button>
    );
}

export function PhotoGallery({
    attachments,
    events,
    description,
}: {
    attachments: Attachment[] | undefined;
    events?: RequestEvent[];
    description?: string;
}) {
    const groups = photoGroups(attachments, events, description);
    if (groups.length === 0) return null;

    return (
        <section className="request-block photo-gallery flex min-w-0 flex-col">
            <CellHeading>{texts.request.photos}</CellHeading>
            <div className="photo-groups">
                {groups.map((group) => (
                    <div key={`${group.kind}-${group.photos[0]?.id ?? group.comment}`} className="photo-with-comment">
                        <p className="photo-group-label">{group.label}</p>
                        <PhotoGrid>
                            {group.photos.map((attachment, index) => (
                                <RequestPhoto
                                    key={attachment.id}
                                    url={attachment.url}
                                    alt={`${group.label} ${index + 1}`}
                                />
                            ))}
                        </PhotoGrid>
                        {group.comment ? (
                            <div className="photo-caption">
                                <p className="photo-caption-label">{texts.request.photoComment}</p>
                                <p className="photo-caption-text">{group.comment}</p>
                            </div>
                        ) : null}
                    </div>
                ))}
            </div>
        </section>
    );
}
