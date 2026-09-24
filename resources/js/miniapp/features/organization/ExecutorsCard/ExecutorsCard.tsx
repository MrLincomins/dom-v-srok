import { Button, CellSimple } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { EmptyState } from '@/components/EmptyState';
import { Notice } from '@/components/Notice';
import { PhoneField } from '@/components/PhoneField';
import { Section } from '@/components/Section';
import { SettingsField } from '@/components/SettingsField';
import { MutationError } from '@/features/request/MutationError';
import { useExecutorsCard } from './ExecutorsCard.model';

export function ExecutorsCard() {
    const card = useExecutorsCard();

    return (
        <form className="flex min-w-0 flex-col gap-16" noValidate onSubmit={card.submit}>
            <Section title={texts.organization.executors} className="settings-card">
                {(card.executors.data ?? []).map((executor) => (
                    <CellSimple
                        key={executor.id}
                        separator
                        title={executor.name}
                        subtitle={
                            <span className="cell-lines">
                                {executor.specialty ? <span>{executor.specialty}</span> : null}
                                {executor.phone ? <span>{executor.phone}</span> : null}
                            </span>
                        }
                        onClick={() => card.fill(executor)}
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
                                    card.setPendingArchiveId(executor.id);
                                }}
                            >
                                {texts.organization.archiveExecutor}
                            </Button>
                        }
                    />
                ))}
            </Section>
            <SettingsField
                label={texts.organization.executorName}
                value={card.name}
                error={card.nameError}
                onChange={(event) => {
                    card.setName(event.target.value);
                    if (card.nameError) card.setNameError('');
                }}
            />
            <PhoneField
                label={texts.organization.executorPhone}
                value={card.phone}
                error={card.phoneError}
                onChange={(next) => {
                    card.setPhone(next);
                    if (card.phoneError) card.setPhoneError('');
                }}
            />
            <SettingsField
                label={texts.organization.executorSpecialty}
                value={card.specialty}
                onChange={(event) => card.setSpecialty(event.target.value)}
            />
            {card.executors.data?.length === 0 && <EmptyState text={texts.organization.executorsEmpty} />}
            <Button
                type="submit"
                variant="primary"
                size="large"
                stretched
                loading={card.create.isPending || card.update.isPending}
                disabled={card.busy || !card.dirty}
            >
                {card.editing ? texts.organization.saveExecutor : texts.organization.addExecutor}
            </Button>
            <MutationError error={card.create.error ?? card.update.error ?? card.archive.error} />
            <ConfirmDialog
                open={card.pendingArchiveId !== null}
                title={texts.organization.archiveConfirm}
                confirm={texts.organization.archiveYes}
                cancel={texts.organization.cancel}
                loading={card.busy}
                onCancel={() => card.setPendingArchiveId(null)}
                onConfirm={() => {
                    if (card.pendingArchiveId !== null) card.archive.mutate(card.pendingArchiveId);
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
