import { useNavigate } from 'react-router';
import { CellSimple } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { ErrorState } from '@/components/ErrorState';
import { FullscreenSpinner } from '@/components/FullscreenSpinner';
import { Screen } from '@/components/Screen';
import { Section } from '@/components/Section';
import { ContactsCard } from '../ContactsCard';
import { ContractorsCard } from '../ContractorsCard';
import { ContractsCard } from '../ContractsCard';
import { ExecutorsCard } from '../ExecutorsCard';
import { HousesCard } from '../HousesCard';
import { useOrganizationPage } from './OrganizationPage.model';

export function OrganizationPage() {
    const navigate = useNavigate();
    const { organization, houses, loading, error, retry } = useOrganizationPage();

    if (loading && !organization.data) {
        return <FullscreenSpinner />;
    }

    return (
        <Screen title={texts.organization.title} backTo="/" contentClassName="flex min-w-0 flex-col gap-24">
            {error && !organization.data && <ErrorState error={error} onRetry={retry} />}
            {organization.data && (
                <>
                    <Section>
                        <CellSimple
                            title={texts.journal.title}
                            subtitle={texts.journal.hint}
                            onClick={() => navigate('/journal')}
                        />
                    </Section>
                    <ContactsCard organization={organization.data} />
                    <ContractsCard organization={organization.data} />
                    <ContractorsCard />
                    <HousesCard houses={houses.data ?? []} />
                    <ExecutorsCard />
                </>
            )}
        </Screen>
    );
}
