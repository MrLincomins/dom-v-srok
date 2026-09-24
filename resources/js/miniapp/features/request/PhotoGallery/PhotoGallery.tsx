import type { Attachment } from '@/api/types';
import { texts } from '@/app/texts';
import { CellHeading } from '@/components/CellHeading';
import { openExternalLink } from '@/bridge/maxWebApp';
import { photosWithUrl } from '@/features/request/EventTimeline/EventTimeline.model';

export function RequestPhoto({ url, alt }: { url: string; alt: string }) {
    return (
        <button type="button" className="request-photo-btn" onClick={() => openExternalLink(url)}>
            <img src={url} alt={alt} className="request-photo" loading="lazy" decoding="async" />
        </button>
    );
}

export function PhotoGallery({ attachments }: { attachments: Attachment[] | undefined }) {
    const visible = photosWithUrl(attachments);
    if (visible.length === 0) return null;

    return (
        <section className="request-block flex min-w-0 flex-col gap-8">
            <CellHeading>{texts.request.photos}</CellHeading>
            <div className="history-photos is-gallery">
                {visible.map((attachment, index) => (
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
            </div>
        </section>
    );
}
