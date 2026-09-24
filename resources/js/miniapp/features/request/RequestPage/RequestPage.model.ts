import { useQuery } from '@tanstack/react-query';
import { getRequest } from '@/api/requests';
import { LIVE_REFETCH_MS } from '@/app/live';

export function useRequest(id: number) {
    return useQuery({
        queryKey: ['request', id],
        queryFn: ({ signal }) => getRequest(id, signal),
        enabled: Number.isFinite(id) && id > 0,
        refetchInterval: LIVE_REFETCH_MS,
    });
}
