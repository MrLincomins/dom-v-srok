import { useEffect, useState } from 'react';
import type { NoticeProps } from './Notice.types';

const SHOW_MS = 2400;
const LEAVE_MS = 280;

export function useNotice({ text, onGone }: NoticeProps) {
    const [shown, setShown] = useState<string | null>(text);
    const [leaving, setLeaving] = useState(false);
    const [typedAt, setTypedAt] = useState(0);

    if (text == null && shown != null) {
        setShown(null);
        setLeaving(false);
    } else if (text != null && text !== shown) {
        setShown(text);
        setLeaving(false);
    }

    useEffect(() => {
        if (!shown) return;
        const onType = (event: Event) => {
            if (!isTypingTarget(event.target)) return;
            setLeaving(false);
            setTypedAt((tick) => tick + 1);
        };
        document.addEventListener('input', onType, true);
        return () => document.removeEventListener('input', onType, true);
    }, [shown]);

    useEffect(() => {
        if (!shown || leaving) return;
        const hide = window.setTimeout(() => setLeaving(true), SHOW_MS);
        return () => window.clearTimeout(hide);
    }, [shown, leaving, typedAt]);

    useEffect(() => {
        if (!leaving) return;
        const done = window.setTimeout(onGone, LEAVE_MS);
        return () => window.clearTimeout(done);
    }, [leaving, onGone]);

    return { shown, leaving };
}

function isTypingTarget(target: EventTarget | null): boolean {
    if (!(target instanceof HTMLElement)) return false;
    const tag = target.tagName;
    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || target.isContentEditable;
}
