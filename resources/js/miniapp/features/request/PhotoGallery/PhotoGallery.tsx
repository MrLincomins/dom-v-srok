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
        <section className="request-block flex min-w-0 flex-col gap-8">
            <CellHeading>{texts.request.photos}</CellHeading>
            {groups.map((group) => (
                <div key={group.kind} className="photo-with-comment">
                    <PhotoGrid>
                        {group.photos.map((attachment, index) => (
                            <RequestPhoto
                                key={attachment.id}
                                url={attachment.url}
                                alt={
                                    attachment.kind === 'closing'
                                        ? `${texts.request.closingPhoto} ${index + 1}`
                                        : `${texts.request.residentPhoto} ${index + 1}`
                                }
                            />
                        ))}
                    </PhotoGrid>
                    {group.comment ? <p className="photo-caption">{group.comment}</p> : null}
                </div>
            ))}
        </section>
    );
}
