import { Typography } from '@maxhub/max-ui';
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
        <section className="enter">
            <Typography.Title variant="small-strong" className="mb-8 block">
                {texts.request.photos}
            </Typography.Title>
            <div className="flex snap-x snap-mandatory gap-12 overflow-x-auto scroll-px-12 pb-8">
                {visible.map((attachment, index) => (
                    <button
                        type="button"
                        key={attachment.id}
                        className="app-card block shrink-0 snap-start overflow-hidden"
                        onClick={() => openExternalLink(attachment.url)}
                    >
                        <img
                            src={attachment.url ?? undefined}
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
