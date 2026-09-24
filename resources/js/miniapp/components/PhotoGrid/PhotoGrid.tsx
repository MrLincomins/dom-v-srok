import { Children, useRef, useState, type ReactNode } from 'react';
import { texts } from '@/app/texts';
import { usePhotosPerRow } from './usePhotosPerRow';

export function PhotoGrid({ children, label }: { children: ReactNode; label?: string }) {
    const wrapRef = useRef<HTMLDivElement>(null);
    const perRow = usePhotosPerRow(wrapRef);
    const items = Children.toArray(children);
    const [expanded, setExpanded] = useState(false);
    const overflow = perRow > 0 && items.length > perRow;
    const visible = overflow && !expanded ? items.slice(0, perRow) : items;
    const more = overflow ? items.length - perRow : 0;

    return (
        <div ref={wrapRef} className="photo-grid-wrap">
            <div className="photo-grid" aria-label={label}>
                {visible}
            </div>
            {overflow ? (
                <button
                    type="button"
                    className="photo-grid-more"
                    onClick={() => setExpanded((open) => !open)}
                >
                    {expanded ? texts.request.hidePhotos : texts.request.morePhotos(more)}
                </button>
            ) : null}
        </div>
    );
}
