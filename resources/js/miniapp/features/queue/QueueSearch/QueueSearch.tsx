import { Icon16SearchOutline, Input } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { FieldClear } from '@/components/FieldClear';
import { useQueueSearch } from './QueueSearch.model';

export function QueueSearch({ onSearch }: { onSearch: (value: string) => void }) {
    const search = useQueueSearch(onSearch);

    return (
        <div role="search" className="queue-search min-w-0">
            <Input
                size="large"
                placeholder={texts.queue.search}
                value={search.value}
                onChange={(event) => search.setValue(event.target.value)}
                inputMode="search"
                aria-label={texts.queue.search}
                iconBefore={<Icon16SearchOutline />}
                withClearButton={false}
                iconAfter={
                    search.value.length > 0 ? <FieldClear onClear={() => search.setValue('')} /> : undefined
                }
            />
        </div>
    );
}
