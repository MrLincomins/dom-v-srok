import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
    addComment,
    changeStatus,
    closeRequest,
    confirmRequest,
    getRequest,
    redirectRequest,
} from '@/api/requests';
import type { RequestCard, RequestStatus } from '@/api/types';

export function useRequest(id: number) {
    return useQuery({
        queryKey: ['request', id],
        queryFn: ({ signal }) => getRequest(id, signal),
        enabled: Number.isFinite(id) && id > 0,
    });
}

/** Ответ мутации сразу кладём в карточку; списки перезапрашиваем только при смене её состояния. */
export function useStaffRequestActions(id: number) {
    const client = useQueryClient();
    const syncCard = (card: RequestCard) => {
        client.setQueryData(['request', id], card);
    };
    const syncCardAndLists = (card: RequestCard) => {
        syncCard(card);
        void client.invalidateQueries({ queryKey: ['requests'] });
        void client.invalidateQueries({ queryKey: ['my-requests'] });
    };

    const status = useMutation({
        mutationFn: ({ status, comment }: { status: RequestStatus; comment?: string }) =>
            changeStatus(id, status, comment),
        onSuccess: syncCardAndLists,
    });
    const redirect = useMutation({
        mutationFn: (data: { name: string; phone?: string; note?: string }) => redirectRequest(id, data),
        onSuccess: syncCardAndLists,
    });
    const close = useMutation({
        mutationFn: ({ comment, photos }: { comment: string; photos: File[] }) =>
            closeRequest(id, comment, photos),
        onSuccess: syncCardAndLists,
    });
    const addCommentMutation = useMutation({
        mutationFn: (text: string) => addComment(id, text),
        onSuccess: syncCard,
    });

    return { status, redirect, close, addComment: addCommentMutation };
}

export function useResidentRequestActions(id: number) {
    const client = useQueryClient();
    const confirm = useMutation({
        mutationFn: ({ resolved, comment }: { resolved: boolean; comment?: string }) =>
            confirmRequest(id, resolved, comment),
        onSuccess: (card) => {
            client.setQueryData(['request', id], card);
            void client.invalidateQueries({ queryKey: ['my-requests'] });
        },
    });

    return { confirm };
}

export type StaffRequestActionMutations = ReturnType<typeof useStaffRequestActions>;
