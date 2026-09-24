import { useQuery } from '@tanstack/react-query';
import { getOrganization, listHouses } from '@/api/organization';
import type { OrganizationSectionKey } from './OrganizationPage.model';

export function useOrganizationSection(section: OrganizationSectionKey) {
    const needOrg = section === 'contacts' || section === 'contracts';
    const needHouses = section === 'houses';

    const organization = useQuery({
        queryKey: ['organization'],
        queryFn: ({ signal }) => getOrganization(signal),
        enabled: needOrg,
    });
    const houses = useQuery({
        queryKey: ['organization', 'houses'],
        queryFn: ({ signal }) => listHouses(signal),
        enabled: needHouses,
    });

    const loading = (needOrg && organization.isPending) || (needHouses && houses.isPending);
    const error = organization.error ?? houses.error;
    const failureCount = Math.max(organization.failureCount, houses.failureCount);
    const retry = () => {
        if (needOrg) void organization.refetch();
        if (needHouses) void houses.refetch();
    };

    return { organization, houses, loading, error, failureCount, retry };
}
