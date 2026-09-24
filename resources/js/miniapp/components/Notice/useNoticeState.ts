import { useCallback, useState } from 'react';
import type { NoticeTone } from './Notice.types';

export function useNoticeState() {
    const [text, setText] = useState<string | null>(null);
    const [tone, setTone] = useState<NoticeTone>('success');
    const [revision, setRevision] = useState(0);

    const show = useCallback((next: string, nextTone: NoticeTone) => {
        setTone(nextTone);
        setText(next);
        setRevision((tick) => tick + 1);
    }, []);

    const clear = useCallback(() => setText(null), []);

    return { text, tone, revision, show, clear };
}
