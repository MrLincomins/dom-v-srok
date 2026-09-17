import { useInfiniteQuery } from '@tanstack/react-query';
import { getQueue } from '@/api/requests';
import type { Paginated, QueueQuery, RequestListItem } from '@/api/types';

export type QueueTab = 'new' | 'in_progress' | 'overdue' | 'closed';

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
    return useInfiniteQuery({
        queryKey: ['requests', query],
        queryFn: ({ signal, pageParam }) => getQueuePage(tab, query, pageParam, signal),
        initialPageParam: 1,
        getNextPageParam: (lastPage) =>
            lastPage.meta.current_page < lastPage.meta.last_page ? lastPage.meta.current_page + 1 : undefined,
    });
}

async function getQueuePage(
    tab: QueueTab,
    query: QueueQuery,
    page: number,
    signal: AbortSignal,
): Promise<Paginated<RequestListItem>> {
    const params = { ...query, page, per_page: 30 };
    if (tab !== 'in_progress') return getQueue(params, signal);

    // У API нет группы для всех заявок в работе. Забираем открытые и ожидающие
    // подтверждения отдельно, а затем собираем одну страницу без новых заявок.
    const [open, done] = await Promise.all([
        getQueue(params, signal),
        getQueue({ ...params, status: 'done' }, signal),
    ]);
    const data = [...open.data.filter((request) => request.status !== 'new'), ...done.data].sort(
        compareByDeadline,
    );

    return {
        data,
        meta: {
            ...open.meta,
            last_page: Math.max(open.meta.last_page, done.meta.last_page),
            per_page: open.meta.per_page + done.meta.per_page,
            total: open.meta.counters?.in_progress ?? data.length,
        },
    };
}

function compareByDeadline(left: RequestListItem, right: RequestListItem): number {
    return deadlineTime(left.deadline_fix_at) - deadlineTime(right.deadline_fix_at);
}

function deadlineTime(value: string | null | undefined): number {
    if (!value) return Number.POSITIVE_INFINITY;
    const time = Date.parse(value);
    return Number.isNaN(time) ? Number.POSITIVE_INFINITY : time;
}
