import type { Attachment } from '@/api/types';
import { texts } from '@/app/texts';
import { openExternalLink } from '@/bridge/maxWebApp';

export function PhotoGallery({ attachments }: { attachments: Attachment[] | undefined }) {
    const visible =
        attachments?.filter(
            (attachment): attachment is Attachment & { url: string } =>
                typeof attachment.url === 'string' && attachment.url.length > 0,
        ) ?? [];
    if (visible.length === 0) return null;

    return (
        <section className="request-block flex min-w-0 flex-col gap-8">
            <h3 className="request-block-title">{texts.request.photos}</h3>
            <div className="flex snap-x snap-mandatory gap-12 overflow-x-auto px-12 pb-8 scroll-px-12">
                {visible.map((attachment, index) => (
                    <button
                        type="button"
                        key={attachment.id}
                        className="block shrink-0 snap-start overflow-hidden rounded-card"
                        onClick={() => openExternalLink(attachment.url)}
                    >
                        <img
                            src={attachment.url}
                            alt={
                                attachment.kind === 'closing'
                                    ? `${texts.request.closingPhoto} ${index + 1}`
                                    : `${texts.request.residentPhoto} ${index + 1}`
                            }
                            loading="lazy"
                            decoding="async"
                            className="h-128 w-128 object-cover"
                        />
                    </button>
                ))}
            </div>
        </section>
    );
}
