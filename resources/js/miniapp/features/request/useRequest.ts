import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { addComment, changeStatus, confirmRequest, getRequest, redirectRequest } from '@/api/requests';
import type { RequestCard, RequestStatus } from '@/api/types';

export function useRequest(id: number) {
    return useQuery({
        queryKey: ['request', id],
        queryFn: ({ signal }) => getRequest(id, signal),
        enabled: Number.isFinite(id) && id > 0,
    });
}

/** мутации карточки, после каждой обновляем карточку и очередь */
export function useRequestActions(id: number) {
    const client = useQueryClient();
    const settle = (card: RequestCard) => {
        client.setQueryData(['request', id], card);
        void client.invalidateQueries({ queryKey: ['requests'] });
    };

    const status = useMutation({
        mutationFn: ({ status, comment }: { status: RequestStatus; comment?: string }) =>
            changeStatus(id, status, comment),
        onSuccess: settle,
    });
    const redirect = useMutation({
        mutationFn: (data: { name: string; phone?: string; note?: string }) => redirectRequest(id, data),
        onSuccess: settle,
    });
    const comment = useMutation({ mutationFn: (text: string) => addComment(id, text), onSuccess: settle });
    const confirm = useMutation({
        mutationFn: ({ resolved, comment }: { resolved: boolean; comment?: string }) =>
            confirmRequest(id, resolved, comment),
        onSuccess: settle,
    });

    return { status, redirect, comment, confirm };
}
