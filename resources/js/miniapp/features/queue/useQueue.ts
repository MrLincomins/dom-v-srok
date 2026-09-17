import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { getQueue } from '@/api/requests';
import type { QueueQuery } from '@/api/types';

export type QueueTab = 'new' | 'in_progress' | 'overdue' | 'closed';

/** вкладка в параметры запроса, «в работе» это назначено, в работе, возвращено и выполнено */
export function tabToQuery(tab: QueueTab, search: string): QueueQuery {
    const q = search.trim() || undefined;
    switch (tab) {
        case 'new':
            return { status: 'new', q };
        case 'in_progress':
            return { status: 'open', q };
        case 'overdue':
            return { status: 'open', overdue: true, q };
        case 'closed':
            return { status: 'closed', q };
    }
}

export function useQueue(tab: QueueTab, search: string) {
    const query = tabToQuery(tab, search);
    return useQuery({
        queryKey: ['requests', query],
        queryFn: ({ signal }) => getQueue(query, signal),
        placeholderData: keepPreviousData,
    });
}
