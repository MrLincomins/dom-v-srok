import { useMutation, useQueryClient } from '@tanstack/react-query';
import { updateOrganization } from '@/api/organization';
import type { Organization } from '@/api/types';

export function useContractsCard(organization: Organization) {
    const client = useQueryClient();
    const save = useMutation({
        mutationFn: (direct_contracts: Organization['direct_contracts']) =>
            updateOrganization({ direct_contracts }),
        onMutate: async (direct_contracts) => {
            await client.cancelQueries({ queryKey: ['organization'] });
            const previous = client.getQueryData<Organization>(['organization']);
            client.setQueryData(['organization'], (current: Organization | undefined) =>
                current ? { ...current, direct_contracts } : current,
            );
            return { previous };
        },
        onError: (_error, _next, context) => {
            if (context?.previous) client.setQueryData(['organization'], context.previous);
        },
        onSuccess: (data) => client.setQueryData(['organization'], data),
    });

    return { organization, save };
}
