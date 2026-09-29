import { useEffect, useEffectEvent, useState } from 'react';

export const SEARCH_DEBOUNCE_MS = 300;

export function useQueueSearch(applied: string, onSearch: (value: string) => void) {
    const [value, setValue] = useState(applied);
    const apply = useEffectEvent((next: string) => onSearch(next));

    useEffect(() => {
        const next = value.trim();
        if (next === applied) return undefined;
        const timer = window.setTimeout(() => apply(next), SEARCH_DEBOUNCE_MS);
        return () => window.clearTimeout(timer);
    }, [applied, value]);

    const submit = () => {
        const next = value.trim();
        if (next !== applied) onSearch(next);
    };

    const clear = () => {
        setValue('');
        if (applied !== '') onSearch('');
    };

    return { value, setValue, submit, clear };
}
