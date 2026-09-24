import { useCallback, useState } from 'react';
import { describeError } from '@/lib/describeError';

export function useErrorNotice() {
    const [text, setText] = useState<string | null>(null);
    const show = useCallback((error: unknown, failureCount = 1) => {
        setText(describeError(error, failureCount));
    }, []);

    const clear = useCallback(() => setText(null), []);

    return { text, show, clear };
}
