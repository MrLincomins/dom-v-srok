import { useEffect, useState } from 'react';
import { Input } from '@maxhub/max-ui';
import { texts } from '@/app/texts';

export function QueueSearch({ onSearch }: { onSearch: (value: string) => void }) {
    const [value, setValue] = useState('');

    useEffect(() => {
        const timer = window.setTimeout(() => onSearch(value), 250);
        return () => window.clearTimeout(timer);
    }, [onSearch, value]);

    return (
        <div role="search">
            <Input
                placeholder={texts.queue.search}
                value={value}
                onChange={(event) => setValue(event.target.value)}
                withClearButton
                inputMode="search"
                aria-label={texts.queue.search}
            />
        </div>
    );
}
