import { useMutation, useQueryClient } from '@tanstack/react-query';
import {
    addComment,
    assignExecutor,
    changeStatus,
    closeRequest,
    redirectRequest,
} from '@/api/requests';
import type { RequestCard, RequestStatus } from '@/api/types';

/** Карточку обновляем сразу. Списки — только если у заявки сменилось состояние. */
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
    const assign = useMutation({
        mutationFn: (executorId: number) => assignExecutor(id, executorId),
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

    return { status, assign, redirect, close, addComment: addCommentMutation };
}

export type StaffRequestActionMutations = ReturnType<typeof useStaffRequestActions>;
