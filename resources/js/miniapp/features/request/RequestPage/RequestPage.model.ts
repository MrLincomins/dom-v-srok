import { useQuery } from '@tanstack/react-query';
import { getRequest } from '@/api/requests';

export function useRequest(id: number) {
    return useQuery({
        queryKey: ['request', id],
        queryFn: ({ signal }) => getRequest(id, signal),
        enabled: Number.isFinite(id) && id > 0,
    });
}
