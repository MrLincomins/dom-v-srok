import { useNavigate } from 'react-router';
import { CellSimple } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { LogoutButton } from '@/components/LogoutButton';
import { Screen } from '@/components/Screen';
import { Section } from '@/components/Section';
import { ORGANIZATION_SECTIONS } from './OrganizationPage.model';

export function OrganizationPage() {
    const navigate = useNavigate();

    return (
        <Screen title={texts.organization.title} backTo="/" contentClassName="flex min-w-0 flex-col gap-24">
            <Section>
                <CellSimple
                    title={texts.journal.title}
                    subtitle={texts.journal.hint}
                    onClick={() => navigate('/journal')}
                />
            </Section>
            <Section>
                {ORGANIZATION_SECTIONS.map((item, index) => (
                    <CellSimple
                        key={item.to}
                        separator={index < ORGANIZATION_SECTIONS.length - 1}
                        title={item.title}
                        subtitle={item.hint}
                        onClick={() => navigate(item.to)}
                    />
                ))}
            </Section>
            <LogoutButton />
        </Screen>
    );
}
