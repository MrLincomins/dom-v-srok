import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Button, CellSimple, Switch, Typography } from '@maxhub/max-ui';
import {
    archiveExecutor,
    createExecutor,
    getOrganization,
    listExecutors,
    listHouses,
    updateExecutor,
    updateHouse,
    updateOrganization,
} from '@/api/organization';
import type { Executor, House, Organization } from '@/api/types';
import { texts } from '@/app/texts';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { DelayedSkeleton } from '@/components/DelayedSkeleton';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { Notice } from '@/components/Notice';
import { PhoneField } from '@/components/PhoneField';
import { Screen } from '@/components/Screen';
import { Section } from '@/components/Section';
import { SettingsField } from '@/components/SettingsField';
import { copyToClipboard } from '@/lib/clipboard';
import { isCompletePhone, localPhoneDigits, toStoredPhone } from '@/lib/phone';
import { MutationError } from '@/features/request/MutationError';

const CONTRACTS = ['cold_water', 'hot_water', 'heat', 'power', 'tko'] as const;

export function OrganizationPage() {
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
    const loading = organization.isPending || houses.isPending || executors.isPending;
    const error = organization.error ?? houses.error ?? executors.error;

    return (
        <Screen title={texts.organization.title} backTo="/" contentClassName="flex min-w-0 flex-col gap-24">
            <DelayedSkeleton loading={loading && !organization.data} rows={4} />
            {error && !organization.data && (
                <ErrorState
                    error={error}
                    onRetry={() => {
                        void organization.refetch();
                        void houses.refetch();
                        void executors.refetch();
                    }}
                />
            )}
            {organization.data && (
                <>
                    <ContactsCard organization={organization.data} />
                    <ContractsCard organization={organization.data} />
                    <HousesCard houses={houses.data ?? []} />
                    <ExecutorsCard />
                </>
            )}
        </Screen>
    );
}

function ContactsCard({ organization }: { organization: Organization }) {
    const client = useQueryClient();
    const [name, setName] = useState(organization.name);
    const [phoneAds, setPhoneAds] = useState(organization.phone_ads);
    const [phoneDispatch, setPhoneDispatch] = useState(organization.phone_dispatch ?? '');
    const [email, setEmail] = useState(organization.email ?? '');
    const [hours, setHours] = useState(organization.reception_hours ?? '');
    const [address, setAddress] = useState(organization.reception_address ?? '');
    const [notice, setNotice] = useState<string | null>(null);
    const save = useMutation({
        mutationFn: () =>
            updateOrganization({
                name: name.trim(),
                phone_ads: phoneAds.trim(),
                phone_dispatch: phoneDispatch.trim() || null,
                email: email.trim() || null,
                reception_hours: hours.trim() || null,
                reception_address: address.trim() || null,
            }),
        onSuccess: (data) => {
            client.setQueryData(['organization'], data);
            setNotice(texts.organization.saved);
        },
    });

    return (
        <form
            className="flex min-w-0 flex-col gap-16"
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                save.mutate();
            }}
        >
            <div className="request-block flex min-w-0 flex-col gap-8">
                <h3 className="request-block-title">{texts.organization.types[organization.type]}</h3>
                <SettingsField
                    label={texts.organization.name}
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                />
                <SettingsField
                    label={texts.organization.phoneAds}
                    value={phoneAds}
                    onChange={(event) => setPhoneAds(event.target.value)}
                    inputMode="tel"
                />
                <SettingsField
                    label={texts.organization.phoneDispatch}
                    value={phoneDispatch}
                    onChange={(event) => setPhoneDispatch(event.target.value)}
                    inputMode="tel"
                />
                <SettingsField
                    label={texts.organization.email}
                    value={email}
                    onChange={(event) => setEmail(event.target.value)}
                    inputMode="email"
                />
                <SettingsField
                    label={texts.organization.hours}
                    value={hours}
                    onChange={(event) => setHours(event.target.value)}
                />
                <SettingsField
                    label={texts.organization.address}
                    value={address}
                    onChange={(event) => setAddress(event.target.value)}
                />
            </div>
            <Button type="submit" variant="primary" size="large" stretched loading={save.isPending}>
                {texts.organization.save}
            </Button>
            <MutationError error={save.error} />
            <Notice text={notice} onGone={() => setNotice(null)} />
        </form>
    );
}

function ContractsCard({ organization }: { organization: Organization }) {
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
                                checked={Boolean(organization.direct_contracts[key])}
                                aria-label={texts.organization.contractsLabels[key]}
                                onChange={(event) =>
                                    save.mutate({
                                        ...organization.direct_contracts,
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
            <MutationError error={save.error} />
        </div>
    );
}

function HousesCard({ houses }: { houses: House[] }) {
    const client = useQueryClient();
    const [notice, setNotice] = useState<string | null>(null);
    const save = useMutation({
        mutationFn: ({
            id,
            chat_keywords_enabled,
            entrances,
        }: {
            id: number;
            chat_keywords_enabled?: boolean;
            entrances?: number;
        }) => updateHouse(id, { chat_keywords_enabled, entrances }),
        onMutate: async ({ id, chat_keywords_enabled, entrances }) => {
            await client.cancelQueries({ queryKey: ['organization', 'houses'] });
            const previous = client.getQueryData<House[]>(['organization', 'houses']);
            client.setQueryData(['organization', 'houses'], (current: House[] | undefined) =>
                (current ?? []).map((item) =>
                    item.id === id
                        ? {
                              ...item,
                              ...(chat_keywords_enabled === undefined ? {} : { chat_keywords_enabled }),
                              ...(entrances === undefined ? {} : { entrances }),
                          }
                        : item,
                ),
            );
            return { previous };
        },
        onError: (_error, _next, context) => {
            if (context?.previous) client.setQueryData(['organization', 'houses'], context.previous);
        },
        onSuccess: (house) => {
            client.setQueryData(['organization', 'houses'], (current: House[] | undefined) =>
                (current ?? []).map((item) => (item.id === house.id ? house : item)),
            );
        },
    });

    if (houses.length === 0) return null;

    return (
        <div className="flex min-w-0 flex-col gap-24">
            {houses.map((house) => (
                <div key={house.id} className="flex min-w-0 flex-col gap-8">
                    <Section title={house.address} className="settings-card">
                        <CellSimple
                            separator
                            title={
                                house.chat_bound
                                    ? texts.organization.houseChat
                                    : texts.organization.houseNoChat
                            }
                        />
                        <CellSimple
                            separator
                            title={texts.organization.joinInChat}
                            after={
                                <Switch
                                    checked={house.chat_keywords_enabled}
                                    disabled={!house.chat_bound}
                                    aria-label={texts.organization.joinInChat}
                                    onChange={(event) =>
                                        save.mutate({
                                            id: house.id,
                                            chat_keywords_enabled: event.currentTarget.checked,
                                        })
                                    }
                                />
                            }
                        />
                        <CellSimple
                            title={texts.organization.openHouse}
                            subtitle={house.start_url}
                            onClick={() => {
                                void copyToClipboard(house.start_url).then((ok) => {
                                    if (ok) setNotice(texts.organization.houseLinkCopied);
                                });
                            }}
                        />
                    </Section>
                    <SettingsField
                        key={house.entrances}
                        label={texts.organization.entrances}
                        defaultValue={String(house.entrances)}
                        inputMode="numeric"
                        disabled={save.isPending}
                        onBlur={(event) => {
                            const next = Number(event.currentTarget.value);
                            if (
                                !Number.isInteger(next) ||
                                next < 1 ||
                                next > 50 ||
                                next === house.entrances
                            ) {
                                event.currentTarget.value = String(house.entrances);
                                return;
                            }
                            save.mutate({ id: house.id, entrances: next });
                        }}
                    />
                    <Typography.Body variant="small" className="settings-hint">
                        {texts.organization.joinInChatHint}
                    </Typography.Body>
                </div>
            ))}
            <MutationError error={save.error} />
            <Notice text={notice} onGone={() => setNotice(null)} />
        </div>
    );
}

function ExecutorsCard() {
    const client = useQueryClient();
    const executors = useQuery({
        queryKey: ['organization', 'executors'],
        queryFn: ({ signal }) => listExecutors(signal),
    });
    const [editing, setEditing] = useState<Executor | null>(null);
    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [specialty, setSpecialty] = useState('');
    const [nameError, setNameError] = useState('');
    const [phoneError, setPhoneError] = useState('');
    const [pendingArchiveId, setPendingArchiveId] = useState<number | null>(null);
    const [notice, setNotice] = useState<string | null>(null);

    const fill = (executor: Executor | null) => {
        setEditing(executor);
        setName(executor?.name ?? '');
        setPhone(localPhoneDigits(executor?.phone ?? ''));
        setSpecialty(executor?.specialty ?? '');
        setNameError('');
        setPhoneError('');
    };

    const create = useMutation({
        mutationFn: createExecutor,
        onSuccess: () => {
            fill(null);
            setNotice(texts.organization.executorAdded);
            void client.invalidateQueries({ queryKey: ['organization', 'executors'] });
        },
    });
    const update = useMutation({
        mutationFn: (id: number) =>
            updateExecutor(id, {
                name: name.trim(),
                phone: toStoredPhone(phone),
                specialty: specialty.trim() || null,
            }),
        onSuccess: () => {
            fill(null);
            setNotice(texts.organization.executorSaved);
            void client.invalidateQueries({ queryKey: ['organization', 'executors'] });
        },
    });
    const archive = useMutation({
        mutationFn: archiveExecutor,
        onSuccess: () => {
            fill(null);
            setPendingArchiveId(null);
            setNotice(texts.organization.executorRemoved);
            void client.invalidateQueries({ queryKey: ['organization', 'executors'] });
        },
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const trimmed = name.trim();
        if (!trimmed) {
            setNameError(texts.organization.executorNameRequired);
            return;
        }
        if (phone.length > 0 && !isCompletePhone(phone)) {
            setPhoneError(texts.organization.phoneError);
            return;
        }
        setNameError('');
        setPhoneError('');
        const storedPhone = toStoredPhone(phone) ?? undefined;
        if (editing) {
            update.mutate(editing.id);
            return;
        }
        create.mutate({
            name: trimmed,
            phone: storedPhone,
            specialty: specialty.trim() || undefined,
        });
    };

    const busy = create.isPending || update.isPending || archive.isPending;

    return (
        <form className="flex min-w-0 flex-col gap-16" noValidate onSubmit={submit}>
            <Section title={texts.organization.executors} className="settings-card">
                {(executors.data ?? []).map((executor) => (
                    <CellSimple
                        key={executor.id}
                        separator
                        title={executor.name}
                        subtitle={[executor.specialty, executor.phone].filter(Boolean).join(' · ')}
                        onClick={() => fill(executor)}
                        after={
                            <Button
                                type="button"
                                size="small"
                                variant="ghost"
                                disabled={busy}
                                onClick={(event) => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    setPendingArchiveId(executor.id);
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
                value={name}
                error={nameError}
                onChange={(event) => {
                    setName(event.target.value);
                    if (nameError) setNameError('');
                }}
            />
            <PhoneField
                label={texts.organization.executorPhone}
                value={phone}
                error={phoneError}
                onChange={(next) => {
                    setPhone(next);
                    if (phoneError) setPhoneError('');
                }}
            />
            <SettingsField
                label={texts.organization.executorSpecialty}
                value={specialty}
                onChange={(event) => setSpecialty(event.target.value)}
            />
            {executors.data?.length === 0 && <EmptyState text={texts.organization.executorsEmpty} />}
            <Button
                type="submit"
                variant="primary"
                size="large"
                stretched
                loading={create.isPending || update.isPending}
                disabled={busy}
            >
                {editing ? texts.organization.saveExecutor : texts.organization.addExecutor}
            </Button>
            <MutationError error={create.error ?? update.error ?? archive.error} />
            {pendingArchiveId !== null && (
                <ConfirmDialog
                    title={texts.organization.archiveConfirm}
                    confirm={texts.organization.archiveYes}
                    cancel={texts.organization.cancel}
                    loading={archive.isPending}
                    onCancel={() => setPendingArchiveId(null)}
                    onConfirm={() => archive.mutate(pendingArchiveId)}
                />
            )}
            <Notice text={notice} onGone={() => setNotice(null)} />
        </form>
    );
}
