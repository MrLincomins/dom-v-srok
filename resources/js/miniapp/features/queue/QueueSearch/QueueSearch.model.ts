import { useEffect, useState } from 'react';

export const SEARCH_DEBOUNCE_MS = 500;

export function useQueueSearch(onSearch: (value: string) => void) {
    const [value, setValue] = useState('');

    useEffect(() => {
        const timer = window.setTimeout(() => onSearch(value), SEARCH_DEBOUNCE_MS);
        return () => window.clearTimeout(timer);
    }, [onSearch, value]);

    return { value, setValue };
}
