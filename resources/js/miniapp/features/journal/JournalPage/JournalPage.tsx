import { useNavigate } from 'react-router';
import { Button, Typography } from '@maxhub/max-ui';
import type { JournalSummary } from '@/api/types';
import { texts } from '@/app/texts';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { FullscreenSpinner } from '@/components/FullscreenSpinner';
import { Notice } from '@/components/Notice';
import { RequestRow } from '@/components/RequestRow';
import { Screen } from '@/components/Screen';
import { SettingsField } from '@/components/SettingsField';
import { MutationError } from '@/features/request/MutationError';
import { formatDateInput } from '@/lib/dates';
import { JOURNAL_STATS, useJournalPage } from './JournalPage.model';

export function JournalPage() {
    const navigate = useNavigate();
    const page = useJournalPage();

    if (page.journal.isPending && !page.journal.data && page.periodError === null) {
        return <FullscreenSpinner />;
    }

    return (
        <Screen
            title={texts.journal.title}
            backTo="/organization"
            contentClassName="flex min-w-0 flex-col gap-16"
        >
            <div className="journal-period">
                <SettingsField
                    label={texts.journal.from}
                    type="date"
                    value={page.from}
                    max={page.to}
                    onChange={(event) => page.setPeriod('from', event.target.value)}
                />
                <SettingsField
                    label={texts.journal.to}
                    type="date"
                    value={page.to}
                    min={page.from}
                    max={formatDateInput()}
                    onChange={(event) => page.setPeriod('to', event.target.value)}
                />
                <Typography.Body variant="small" className="settings-hint">
                    {texts.journal.periodHint}
                </Typography.Body>
                {page.periodError && (
                    <Typography.Body variant="small" className="px-16 text-negative" role="alert">
                        {page.periodError}
                    </Typography.Body>
                )}
            </div>
            <Button
                type="button"
                variant="secondary"
                size="large"
                stretched
                disabled={page.periodError !== null}
                loading={page.csv.isPending}
                onClick={() => page.csv.mutate()}
            >
                {texts.journal.csv}
            </Button>
            <MutationError error={page.csv.error} />
            {page.summary && page.periodError === null && <JournalStats summary={page.summary} />}
            {page.journal.isError && !page.journal.data && (
                <ErrorState
                    error={page.journal.error}
                    failureCount={page.journal.failureCount}
                    onRetry={() => void page.journal.refetch()}
                />
            )}
            {page.ready && page.items.length === 0 && !page.journal.isError && (
                <EmptyState text={texts.journal.empty} />
            )}
            <div className={`request-list ${page.journal.isPlaceholderData ? 'opacity-60' : ''}`}>
                {page.items.map((row) => (
                    <RequestRow
                        key={row.id}
                        item={row}
                        detail={[row.resident_name, row.responsible_name].filter(Boolean).join(' · ')}
                        onOpen={(id) => navigate(`/requests/${id}`)}
                    />
                ))}
            </div>
            {page.journal.hasNextPage && page.ready && !page.journal.isError && (
                <Button
                    variant="secondary"
                    size="large"
                    stretched
                    loading={page.journal.isFetchingNextPage}
                    onClick={() => void page.journal.fetchNextPage()}
                >
                    {texts.queue.loadMore}
                </Button>
            )}
            <Notice text={page.notice} onGone={() => page.setNotice(null)} />
        </Screen>
    );
}

function JournalStats({ summary }: { summary: JournalSummary }) {
    return (
        <div className="journal-stats">
            {JOURNAL_STATS.map((stat) => (
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
