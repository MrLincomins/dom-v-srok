import { useEffect, useState } from 'react';
import type { NoticeProps } from './Notice.types';

const SHOW_MS = 2400;
const LEAVE_MS = 280;

export function useNotice({ text, onGone }: NoticeProps) {
    const [shown, setShown] = useState<string | null>(text);
    const [leaving, setLeaving] = useState(false);

    if (text == null && shown != null) {
        setShown(null);
        setLeaving(false);
    } else if (text != null && text !== shown) {
        setShown(text);
        setLeaving(false);
    }

    useEffect(() => {
        if (!shown) return;
        const hide = window.setTimeout(() => setLeaving(true), SHOW_MS);
        return () => window.clearTimeout(hide);
    }, [shown]);

    useEffect(() => {
        if (!leaving) return;
        const done = window.setTimeout(onGone, LEAVE_MS);
        return () => window.clearTimeout(done);
    }, [leaving, onGone]);

    return { shown, leaving };
}
