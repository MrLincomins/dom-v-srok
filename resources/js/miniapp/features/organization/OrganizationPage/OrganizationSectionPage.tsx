import { Navigate, useParams } from 'react-router';
import { texts } from '@/app/texts';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { FullscreenSpinner } from '@/components/FullscreenSpinner';
import { Screen } from '@/components/Screen';
import { ContactsCard } from '../ContactsCard';
import { ContractorsCard } from '../ContractorsCard';
import { ContractsCard } from '../ContractsCard';
import { ExecutorsCard } from '../ExecutorsCard';
import { HousesCard } from '../HousesCard';
import { isOrganizationSection, sectionTitle, type OrganizationSectionKey } from './OrganizationPage.model';
import { useOrganizationSection } from './OrganizationSectionPage.model';

export function OrganizationSectionPage() {
    const { section } = useParams();
    if (!isOrganizationSection(section)) return <Navigate to="/organization" replace />;

    return (
        <Screen title={sectionTitle(section)} backTo="/organization" contentClassName="flex min-w-0 flex-col gap-24">
            <SectionBody section={section} />
        </Screen>
    );
}

function SectionBody({ section }: { section: OrganizationSectionKey }) {
    const page = useOrganizationSection(section);

    if (section === 'executors') return <ExecutorsCard />;
    if (section === 'contractors') return <ContractorsCard />;

    if (page.loading && !page.organization.data && !page.houses.data) {
        return <FullscreenSpinner />;
    }
    if (page.error && !page.organization.data && !page.houses.data) {
        return <ErrorState error={page.error} failureCount={page.failureCount} onRetry={page.retry} />;
    }
    if (section === 'contacts' && page.organization.data) {
        return <ContactsCard organization={page.organization.data} />;
    }
    if (section === 'contracts' && page.organization.data) {
        return <ContractsCard organization={page.organization.data} />;
    }
    if (section === 'houses') {
        const houses = page.houses.data ?? [];
        if (houses.length === 0) return <EmptyState text={texts.organization.housesEmpty} />;
        return <HousesCard houses={houses} />;
    }
    return null;
}
