import { createPortal } from 'react-dom';
import { useNotice } from './Notice.model';
import type { NoticeProps } from './Notice.types';

export function Notice({ text, tone, revision, onGone }: NoticeProps) {
    const { shown, leaving } = useNotice({ text, tone, revision, onGone });
    if (!shown) return null;

    const leaveClass = leaving ? ' is-leaving' : '';

    return createPortal(
        <div className={`notice is-${tone}${leaveClass}`} role="status">
            {shown}
        </div>,
        document.querySelector('.max-root') ?? document.body,
    );
}
