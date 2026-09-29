import { useEffect, useMemo } from 'react';
import { useSearchParams } from 'react-router';
import { keepPreviousData, useInfiniteQuery, useMutation } from '@tanstack/react-query';
import { getJournal, getJournalCsvLink } from '@/api/journal';
import { texts } from '@/app/texts';
import { isInsideMax, openFileLink } from '@/bridge/maxWebApp';
import { useNoticeState } from '@/components/Notice';
import { daysAgoInput, daysBetween, formatDateInput } from '@/lib/dates';
import type { JournalStat } from './JournalPage.types';

const JOURNAL_DEFAULT_DAYS = 30;

export const JOURNAL_STATS: JournalStat[] = [
    { key: 'total', label: texts.journal.summary.total },
    { key: 'open', label: texts.journal.summary.open },
    { key: 'overdue', label: texts.journal.summary.overdue },
    { key: 'closed', label: texts.journal.summary.closed },
    { key: 'on_time', label: texts.journal.summary.on_time },
    { key: 'late', label: texts.journal.summary.late },
    { key: 'returned', label: texts.journal.summary.returned },
    { key: 'redirected', label: texts.journal.summary.redirected },
];

export function describePeriod(from: string, to: string): string | null {
    const days = daysBetween(from, to);
    if (days == null || days < 0) return texts.journal.periodInvalid;
    if (days > 366) return texts.journal.periodTooLong;
    return null;
}

export function useJournalPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const notice = useNoticeState();
    const showNotice = notice.show;
    const from = searchParams.get('from') || daysAgoInput(JOURNAL_DEFAULT_DAYS);
    const to = searchParams.get('to') || formatDateInput();
    const periodError = describePeriod(from, to);

    useEffect(() => {
        if (!periodError) return;
        showNotice(periodError, 'error');
    }, [periodError, showNotice]);

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
        mutationFn: async () => {
            const link = await getJournalCsvLink({ from, to });
            openFileLink(link.url);
        },
        onSuccess: () => {
            showNotice(isInsideMax() ? texts.journal.csvOpened : texts.journal.csvStarted, 'success');
        },
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

    return {
        from,
        to,
        periodError,
        journal,
        csv,
        setPeriod,
        items,
        summary,
        ready,
        notice,
    };
}
