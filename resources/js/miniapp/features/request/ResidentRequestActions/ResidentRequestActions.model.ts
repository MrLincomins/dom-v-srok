import { useMutation, useQueryClient } from '@tanstack/react-query';
import { confirmRequest } from '@/api/requests';

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
