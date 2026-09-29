import type { FormEvent } from 'react';
import { Icon16SearchOutline, Input } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { FieldClear } from '@/components/FieldClear';
import { useQueueSearch } from './QueueSearch.model';

export function QueueSearch({ value, onSearch }: { value: string; onSearch: (value: string) => void }) {
    const search = useQueueSearch(value, onSearch);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        search.submit();
        event.currentTarget.querySelector('input')?.blur();
    };

    return (
        <form role="search" className="queue-search min-w-0" onSubmit={submit}>
            <Input
                size="large"
                placeholder={texts.queue.search}
                value={search.value}
                onChange={(event) => search.setValue(event.target.value)}
                inputMode="search"
                enterKeyHint="search"
                aria-label={texts.queue.searchLabel}
                iconBefore={<Icon16SearchOutline />}
                withClearButton={false}
                iconAfter={search.value.length > 0 ? <FieldClear onClear={search.clear} /> : undefined}
            />
        </form>
    );
}
