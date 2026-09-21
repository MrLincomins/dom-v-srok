import { useCallback, useMemo, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router';
import { Button } from '@maxhub/max-ui';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { DelayedSkeleton } from '@/components/DelayedSkeleton';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { RequestRow } from '@/components/RequestRow';
import { Screen } from '@/components/Screen';
import { QueueSearch } from './QueueSearch';
import { QueueTabs } from './QueueTabs';
import { useQueue, type QueueTab } from './useQueue';

export function QueuePage({ backTo }: { backTo?: string } = {}) {
    const navigate = useNavigate();
    const { user } = useAuth();
    const [searchParams, setSearchParams] = useSearchParams();
    const requestedTab = searchParams.get('tab');
    const tab = isQueueTab(requestedTab) ? requestedTab : 'new';
    const [search, setSearch] = useState('');
    const setTab = useCallback(
        (nextTab: QueueTab) => {
            setSearchParams(
                (current) => {
                    if (nextTab === 'new') current.delete('tab');
                    else current.set('tab', nextTab);
                    return current;
                },
                { replace: true },
            );
        },
        [setSearchParams],
    );
    const queue = useQueue(tab, search);

    const pages = queue.data?.pages;
    const items = useMemo(() => pages?.flatMap((page) => page.data) ?? [], [pages]);
    const counters = pages?.[0]?.meta.counters;
    const ready = Boolean(queue.data) && !queue.isPlaceholderData;

    return (
        <Screen
            title={texts.queue.title}
            titleLevel={backTo ? 1 : 2}
            backTo={backTo}
            contentClassName="flex min-w-0 flex-col gap-12"
            right={
                !backTo && user ? (
                    <button
                        type="button"
                        className="queue-who"
                        onClick={() => navigate('/organization')}
                    >
                        {texts.home.organization}
                    </button>
                ) : undefined
            }
        >
            <QueueTabs tab={tab} counters={counters} onChange={setTab} />
            <QueueSearch onSearch={setSearch} />

            <div
                id="queue-panel"
                role="tabpanel"
                aria-labelledby={`queue-tab-${tab}`}
                className={`flex min-w-0 flex-col gap-12 ${queue.isPlaceholderData ? 'opacity-60' : ''}`}
            >
                <DelayedSkeleton loading={queue.isPending && !queue.data} rows={3} />
                {queue.isError && !queue.data && (
                    <ErrorState error={queue.error} onRetry={() => void queue.refetch()} />
                )}
                {ready && items.length === 0 && !queue.isError && (
                    <EmptyState text={texts.queue.empty[tab]} />
                )}
                {items.map((item) => (
                    <RequestRow
                        key={item.id}
                        item={item}
                        onOpen={(id) => navigate(`/requests/${id}`)}
                    />
                ))}
                {queue.hasNextPage && ready && !queue.isError && (
                    <Button
                        variant="secondary"
                        size="large"
                        stretched
                        loading={queue.isFetchingNextPage}
                        onClick={() => void queue.fetchNextPage()}
                    >
                        {texts.queue.loadMore}
                    </Button>
                )}
            </div>
        </Screen>
    );
}

function isQueueTab(value: string | null): value is QueueTab {
    return value === 'new' || value === 'in_progress' || value === 'overdue' || value === 'closed';
}
