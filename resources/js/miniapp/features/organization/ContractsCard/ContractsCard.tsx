import { CellSimple, Switch, Typography } from '@maxhub/max-ui';
import type { Organization } from '@/api/types';
import { texts } from '@/app/texts';
import { Section } from '@/components/Section';
import { MutationError } from '@/features/request/MutationError';
import { CONTRACTS } from '../constants';
import { useContractsCard } from './ContractsCard.model';

export function ContractsCard({ organization }: { organization: Organization }) {
    const card = useContractsCard(organization);

    return (
        <div className="flex min-w-0 flex-col gap-8">
            <Section title={texts.organization.contracts} className="settings-card">
                {CONTRACTS.map((key) => (
                    <CellSimple
                        key={key}
                        separator
                        title={texts.organization.contractsLabels[key]}
                        after={
                            <Switch
                                checked={Boolean(card.organization.direct_contracts[key])}
                                aria-label={texts.organization.contractsLabels[key]}
                                onChange={(event) =>
                                    card.save.mutate({
                                        ...card.organization.direct_contracts,
                                        [key]: event.currentTarget.checked,
                                    })
                                }
                            />
                        }
                    />
                ))}
            </Section>
            <Typography.Body variant="small" className="settings-hint">
                {texts.organization.contractsHint}
            </Typography.Body>
            <MutationError error={card.save.error} />
        </div>
    );
}
