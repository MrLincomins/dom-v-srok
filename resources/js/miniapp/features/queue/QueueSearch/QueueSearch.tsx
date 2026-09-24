import { Icon16SearchOutline, Input } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { useQueueSearch } from './QueueSearch.model';

export function QueueSearch({ onSearch }: { onSearch: (value: string) => void }) {
    const search = useQueueSearch(onSearch);

    return (
        <div role="search" className="min-w-0">
            <Input
                size="large"
                placeholder={texts.queue.search}
                value={search.value}
                onChange={(event) => search.setValue(event.target.value)}
                withClearButton
                inputMode="search"
                aria-label={texts.queue.search}
                iconBefore={<Icon16SearchOutline />}
            />
        </div>
    );
}
