import { Button, CellSimple, Typography } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { InlineLoader } from '@/components/LineLoader';
import { Notice } from '@/components/Notice';
import { PhoneField } from '@/components/PhoneField';
import { Section } from '@/components/Section';
import { SettingsField } from '@/components/SettingsField';
import { MutationError } from '@/features/request/MutationError';
import { CONTRACTOR_TYPES } from '../constants';
import { useContractorsCard } from './ContractorsCard.model';

export function ContractorsCard() {
    const card = useContractorsCard();

    return (
        <form className="flex min-w-0 flex-col gap-16" noValidate onSubmit={card.submit}>
            {!card.contractors.data && card.contractors.isPending ? (
                <InlineLoader />
            ) : !card.contractors.data && card.contractors.isError ? (
                <ErrorState
                    error={card.contractors.error}
                    failureCount={card.contractors.failureCount}
                    onRetry={() => void card.contractors.refetch()}
                />
            ) : (
                <Section title={texts.organization.contractors} className="settings-card">
                    {(card.contractors.data ?? []).map((contractor) => (
                        <CellSimple
                            key={contractor.id}
                            separator
                            title={contractor.name}
                            subtitle={
                                <span className="cell-lines">
                                    {contractor.type_label ? <span>{contractor.type_label}</span> : null}
                                    {contractor.phone ? <span>{contractor.phone}</span> : null}
                                </span>
                            }
                            after={
                                <Button
                                    type="button"
                                    size="small"
                                    variant="secondary"
                                    className="btn-remove"
                                    disabled={card.busy}
                                    onClick={(event) => {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        card.setPendingId(contractor.id);
                                    }}
                                >
                                    {texts.organization.removeContractor}
                                </Button>
                            }
                        />
                    ))}
                </Section>
            )}
            <div className="ios-tabs" role="group" aria-label={texts.organization.contractorType}>
                {CONTRACTOR_TYPES.map((item) => (
                    <button
                        key={item}
                        type="button"
                        className={`ios-tab${card.type === item ? ' is-active' : ''}`}
                        aria-pressed={card.type === item}
                        onClick={() => card.setType(item)}
                    >
                        {texts.organization.contractorTypes[item]}
                    </button>
                ))}
            </div>
            <SettingsField
                label={texts.organization.contractorName}
                value={card.name}
                error={card.nameError}
                onChange={(event) => {
                    card.setName(event.target.value);
                    if (card.nameError) card.setNameError('');
                }}
            />
            <PhoneField
                label={texts.organization.contractorPhone}
                value={card.phone}
                error={card.phoneError}
                onChange={(next) => {
                    card.setPhone(next);
                    if (card.phoneError) card.setPhoneError('');
                }}
            />
            {card.contractors.data?.length === 0 && <EmptyState text={texts.organization.contractorsEmpty} />}
            <Button
                type="submit"
                variant="primary"
                size="large"
                stretched
                loading={card.create.isPending}
                disabled={card.busy || !card.dirty}
            >
                {texts.organization.addContractor}
            </Button>
            <Typography.Body variant="small" className="settings-hint">
                {texts.organization.contractorsHint}
            </Typography.Body>
            <MutationError error={card.create.error ?? card.remove.error} />
            <ConfirmDialog
                open={card.pendingId !== null}
                title={texts.organization.removeContractorConfirm}
                confirm={texts.organization.removeContractorYes}
                cancel={texts.organization.cancel}
                loading={card.busy}
                onCancel={() => card.setPendingId(null)}
                onConfirm={() => {
                    if (card.pendingId !== null) card.remove.mutate(card.pendingId);
                }}
            />
            <Notice
                text={card.notice.text}
                tone={card.notice.tone}
                revision={card.notice.revision}
                onGone={card.notice.clear}
            />
        </form>
    );
}
