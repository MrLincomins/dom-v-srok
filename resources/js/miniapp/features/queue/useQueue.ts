import { useInfiniteQuery } from '@tanstack/react-query';
import { getQueue } from '@/api/requests';
import type { QueueQuery } from '@/api/types';

export type QueueTab = 'new' | 'in_progress' | 'overdue' | 'closed';

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
    });
}
