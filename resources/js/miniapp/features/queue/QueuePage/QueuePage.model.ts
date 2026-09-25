import { keepPreviousData, useInfiniteQuery, useQueries } from '@tanstack/react-query';
import { getQueue } from '@/api/requests';
import { LIVE_REFETCH_MS } from '@/app/live';
import type { QueueQuery } from '@/api/types';

export const QUEUE_TABS = ['new', 'in_progress', 'overdue', 'closed'] as const;
export type QueueTab = (typeof QUEUE_TABS)[number];

export function countersForSearch(
    counters: Partial<Record<QueueTab, number>> | undefined,
    search: string,
    searchTotals?: Partial<Record<QueueTab, number>>,
): Partial<Record<QueueTab, number>> | undefined {
    if (search.trim() === '') return counters;
    return {
        new: searchTotals?.new ?? 0,
        in_progress: searchTotals?.in_progress ?? 0,
        overdue: searchTotals?.overdue ?? 0,
        closed: searchTotals?.closed ?? 0,
    };
}

export function useQueueSearchTotals(search: string, activeTab: QueueTab, activeTotal?: number) {
    const q = search.trim();
    const queries = useQueries({
        queries: QUEUE_TABS.map((tab) => ({
            queryKey: ['requests', 'search-total', tabToQuery(tab, q)] as const,
            queryFn: ({ signal }: { signal: AbortSignal }) =>
                getQueue({ ...tabToQuery(tab, q), page: 1, per_page: 1 }, signal),
            enabled: q !== '' && tab !== activeTab,
            placeholderData: keepPreviousData,
            refetchInterval: LIVE_REFETCH_MS,
        })),
    });

    if (q === '') return undefined;

    const totals: Record<QueueTab, number> = {
        new: 0,
        in_progress: 0,
        overdue: 0,
        closed: 0,
    };
    for (const [index, tab] of QUEUE_TABS.entries()) {
        if (tab === activeTab) {
            totals[tab] = activeTotal ?? 0;
            continue;
        }
        totals[tab] = queries[index]?.data?.meta.total ?? 0;
    }
    return totals;
}

export function tabToQuery(tab: QueueTab, search: string): QueueQuery {
    const q = search.trim() || undefined;
    switch (tab) {
        case 'new':
            return { status: 'new', q };
        case 'in_progress':
            return { status: 'active', q };
        case 'overdue':
            return { status: 'open', overdue: true, q };
        case 'closed':
            return { status: 'closed', q };
    }
}

export function useQueue(tab: QueueTab, search: string) {
    const query = tabToQuery(tab, search);
    return useInfiniteQuery({
        queryKey: ['requests', query],
        queryFn: ({ signal, pageParam }) => getQueue({ ...query, page: pageParam, per_page: 30 }, signal),
        initialPageParam: 1,
        getNextPageParam: (lastPage) =>
            lastPage.meta.current_page < lastPage.meta.last_page ? lastPage.meta.current_page + 1 : undefined,
        placeholderData: keepPreviousData,
        refetchInterval: LIVE_REFETCH_MS,
    });
}
