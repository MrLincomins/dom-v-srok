import { useCallback, useMemo, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router';
import { Button } from '@maxhub/max-ui';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { FullscreenSpinner } from '@/components/FullscreenSpinner';
import { RequestRow } from '@/components/RequestRow';
import { Screen } from '@/components/Screen';
import { QueueSearch } from '@/features/queue/QueueSearch';
import { QueueTabs } from '@/features/queue/QueueTabs';
import { countersForSearch, useQueue, useQueueSearchTotals, type QueueTab } from '@/features/queue/useQueue';

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
    const searchTotals = useQueueSearchTotals(search, tab, pages?.[0]?.meta.total);
    const counters = countersForSearch(pages?.[0]?.meta.counters, search, searchTotals);
    const ready = Boolean(queue.data) && !queue.isPlaceholderData;

    if (queue.isPending && !queue.data) {
        return <FullscreenSpinner />;
    }

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
                className="flex min-w-0 flex-col gap-12"
            >
                {queue.isError && !queue.data && (
                    <ErrorState
                        error={queue.error}
                        failureCount={queue.failureCount}
                        onRetry={() => void queue.refetch()}
                    />
                )}
                {ready && items.length === 0 && !queue.isError && (
                    <EmptyState text={texts.queue.empty[tab]} />
                )}
                <div className={`request-list${queue.isPlaceholderData ? ' is-updating' : ''}`}>
                    {items.map((item) => (
                        <RequestRow
                            key={item.id}
                            item={item}
                            onOpen={(id) => navigate(`/requests/${id}`)}
                        />
                    ))}
                </div>
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
