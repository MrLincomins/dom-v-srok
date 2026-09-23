import { useQuery } from '@tanstack/react-query';
import { getOrganization, listContractors, listExecutors, listHouses } from '@/api/organization';

export function useOrganizationPage() {
    const organization = useQuery({
        queryKey: ['organization'],
        queryFn: ({ signal }) => getOrganization(signal),
    });
    const houses = useQuery({
        queryKey: ['organization', 'houses'],
        queryFn: ({ signal }) => listHouses(signal),
    });
    const executors = useQuery({
        queryKey: ['organization', 'executors'],
        queryFn: ({ signal }) => listExecutors(signal),
    });
    const contractors = useQuery({
        queryKey: ['organization', 'contractors'],
        queryFn: ({ signal }) => listContractors(signal),
    });

    const loading =
        organization.isPending || houses.isPending || executors.isPending || contractors.isPending;
    const error = organization.error ?? houses.error ?? executors.error ?? contractors.error;

    const retry = () => {
        void organization.refetch();
        void houses.refetch();
        void executors.refetch();
        void contractors.refetch();
    };

    return { organization, houses, loading, error, retry };
}
