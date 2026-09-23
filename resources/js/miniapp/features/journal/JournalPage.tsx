import { useMemo, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router';
import { keepPreviousData, useInfiniteQuery, useMutation } from '@tanstack/react-query';
import { Button, Typography } from '@maxhub/max-ui';
import { downloadJournalCsv, getJournal } from '@/api/journal';
import type { JournalSummary } from '@/api/types';
import { texts } from '@/app/texts';
import { DelayedSkeleton } from '@/components/DelayedSkeleton';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { Notice } from '@/components/Notice';
import { RequestRow } from '@/components/RequestRow';
import { Screen } from '@/components/Screen';
import { SettingsField } from '@/components/SettingsField';
import { MutationError } from '@/features/request/MutationError';
import { daysBetween, formatDateInput, startOfMonthInput } from '@/lib/dates';

const STATS: {
    key: Exclude<keyof JournalSummary, 'first_reaction_minutes'>;
    label: string;
    alert?: boolean;
}[] = [
    { key: 'total', label: texts.journal.summary.total },
    { key: 'open', label: texts.journal.summary.open },
    { key: 'overdue', label: texts.journal.summary.overdue, alert: true },
    { key: 'closed', label: texts.journal.summary.closed },
    { key: 'on_time', label: texts.journal.summary.on_time },
    { key: 'late', label: texts.journal.summary.late, alert: true },
    { key: 'returned', label: texts.journal.summary.returned },
    { key: 'redirected', label: texts.journal.summary.redirected },
];

export function JournalPage() {
    const navigate = useNavigate();
    const [searchParams, setSearchParams] = useSearchParams();
    const [notice, setNotice] = useState<string | null>(null);
    const from = searchParams.get('from') || startOfMonthInput();
    const to = searchParams.get('to') || formatDateInput();
    const periodError = describePeriod(from, to);

    const journal = useInfiniteQuery({
        queryKey: ['journal', from, to],
        queryFn: ({ signal, pageParam }) => getJournal({ from, to, page: pageParam, per_page: 30 }, signal),
        initialPageParam: 1,
        enabled: periodError === null,
        getNextPageParam: (lastPage) =>
            lastPage.meta.current_page < lastPage.meta.last_page ? lastPage.meta.current_page + 1 : undefined,
        placeholderData: keepPreviousData,
    });

    const csv = useMutation({
        mutationFn: () => downloadJournalCsv({ from, to }),
        onSuccess: () => setNotice(texts.journal.csvDone),
    });

    const setPeriod = (key: 'from' | 'to', value: string) => {
        setSearchParams(
            (current) => {
                current.set('from', key === 'from' ? value : from);
                current.set('to', key === 'to' ? value : to);
                return current;
            },
            { replace: true },
        );
    };

    const pages = journal.data?.pages;
    const items = useMemo(() => pages?.flatMap((page) => page.data) ?? [], [pages]);
    const summary = pages?.[0]?.meta.summary;
    const ready = Boolean(journal.data) && !journal.isPlaceholderData && periodError === null;

    return (
        <Screen
            title={texts.journal.title}
            backTo="/organization"
            contentClassName="flex min-w-0 flex-col gap-16"
        >
            <div className="flex min-w-0 flex-col gap-8">
                <SettingsField
                    label={texts.journal.from}
                    type="date"
                    value={from}
                    max={to}
                    onChange={(event) => setPeriod('from', event.target.value)}
                />
                <SettingsField
                    label={texts.journal.to}
                    type="date"
                    value={to}
                    min={from}
                    max={formatDateInput()}
                    onChange={(event) => setPeriod('to', event.target.value)}
                />
                <Typography.Body variant="small" className="settings-hint">
                    {texts.journal.periodHint}
                </Typography.Body>
                {periodError && (
                    <Typography.Body variant="small" className="px-16 text-negative" role="alert">
                        {periodError}
                    </Typography.Body>
                )}
            </div>
            <Button
                type="button"
                variant="secondary"
                size="large"
                stretched
                disabled={periodError !== null}
                loading={csv.isPending}
                onClick={() => csv.mutate()}
            >
                {texts.journal.csv}
            </Button>
            <MutationError error={csv.error} />
            {summary && periodError === null && <JournalStats summary={summary} />}
            <DelayedSkeleton loading={journal.isPending && !journal.data && periodError === null} rows={3} />
            {journal.isError && !journal.data && (
                <ErrorState error={journal.error} onRetry={() => void journal.refetch()} />
            )}
            {ready && items.length === 0 && !journal.isError && <EmptyState text={texts.journal.empty} />}
            <div className={`flex min-w-0 flex-col gap-12 ${journal.isPlaceholderData ? 'opacity-60' : ''}`}>
                {items.map((row) => (
                    <RequestRow
                        key={row.id}
                        item={row}
                        detail={[row.resident_name, row.responsible_name].filter(Boolean).join(' · ')}
                        onOpen={(id) => navigate(`/requests/${id}`)}
                    />
                ))}
            </div>
            {journal.hasNextPage && ready && !journal.isError && (
                <Button
                    variant="secondary"
                    size="large"
                    stretched
                    loading={journal.isFetchingNextPage}
                    onClick={() => void journal.fetchNextPage()}
                >
                    {texts.queue.loadMore}
                </Button>
            )}
            <Notice text={notice} onGone={() => setNotice(null)} />
        </Screen>
    );
}

function JournalStats({ summary }: { summary: JournalSummary }) {
    return (
        <div className="journal-stats">
            {STATS.map((stat) => (
                <div
                    key={stat.key}
                    className={`journal-stat${stat.alert && summary[stat.key] > 0 ? ' is-alert' : ''}`}
                >
                    <span className="journal-stat-value tabular">{summary[stat.key]}</span>
                    <span className="journal-stat-label">{stat.label}</span>
                </div>
            ))}
            <div className="journal-stat">
                <span className="journal-stat-value tabular">
                    {summary.first_reaction_minutes == null
                        ? '—'
                        : texts.journal.minutes(summary.first_reaction_minutes)}
                </span>
                <span className="journal-stat-label">{texts.journal.summary.first_reaction}</span>
            </div>
        </div>
    );
}

function describePeriod(from: string, to: string): string | null {
    const days = daysBetween(from, to);
    if (days == null || days < 0) return texts.journal.periodInvalid;
    if (days > 366) return texts.journal.periodTooLong;
    return null;
}
