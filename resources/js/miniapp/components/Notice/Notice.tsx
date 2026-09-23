import { createPortal } from 'react-dom';
import { useNotice } from './Notice.model';
import type { NoticeProps } from './Notice.types';

export function Notice({ text, onGone }: NoticeProps) {
    const { shown, leaving } = useNotice({ text, onGone });
    if (!shown) return null;

    return createPortal(
        <div className={`notice${leaving ? ' is-leaving' : ''}`} role="status">
            {shown}
        </div>,
        document.body,
    );
}
