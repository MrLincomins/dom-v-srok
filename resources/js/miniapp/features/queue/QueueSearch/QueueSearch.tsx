import { useEffect, useState } from 'react';
import { Icon16SearchOutline, Input } from '@maxhub/max-ui';
import { texts } from '@/app/texts';

export function QueueSearch({ onSearch }: { onSearch: (value: string) => void }) {
    const [value, setValue] = useState('');

    useEffect(() => {
        const timer = window.setTimeout(() => onSearch(value), 250);
        return () => window.clearTimeout(timer);
    }, [onSearch, value]);

    return (
        <div role="search" className="min-w-0">
            <Input
                size="large"
                placeholder={texts.queue.search}
                value={value}
                onChange={(event) => setValue(event.target.value)}
                withClearButton
                inputMode="search"
                aria-label={texts.queue.search}
                iconBefore={<Icon16SearchOutline />}
            />
        </div>
    );
}
