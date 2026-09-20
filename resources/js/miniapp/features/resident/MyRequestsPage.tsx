import { useMemo } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { useNavigate, useSearchParams } from 'react-router';
import { Button } from '@maxhub/max-ui';
import { myRequests } from '@/api/requests';
import { texts } from '@/app/texts';
import { DelayedSkeleton } from '@/components/DelayedSkeleton';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { RequestRow } from '@/components/RequestRow';
import { Screen } from '@/components/Screen';

const PAST = new Set(['confirmed', 'redirected']);

export function MyRequestsPage({ backTo }: { backTo?: string }) {
    const navigate = useNavigate();
    const [searchParams, setSearchParams] = useSearchParams();
    const past = searchParams.get('tab') === 'past';
    const list = useInfiniteQuery({
        queryKey: ['my-requests'],
        queryFn: ({ signal, pageParam }) => myRequests(pageParam, signal),
        initialPageParam: 1,
        getNextPageParam: (lastPage) =>
            lastPage.meta.current_page < lastPage.meta.last_page ? lastPage.meta.current_page + 1 : undefined,
    });
    const pages = list.data?.pages;
    const requests = useMemo(() => {
        const all = pages?.flatMap((page) => page.data) ?? [];
        return all.filter((item) => (past ? PAST.has(item.status) : !PAST.has(item.status)));
    }, [pages, past]);

    return (
        <Screen
            title={past ? texts.home.pastRequests : texts.resident.title}
            titleLevel={2}
            backTo={backTo}
            contentClassName="flex min-w-0 flex-col gap-16"
        >
            <nav className="ios-tabs" aria-label={texts.queue.filters} role="tablist">
                <button
                    type="button"
                    className={`ios-tab${!past ? ' is-active' : ''}`}
                    role="tab"
                    aria-selected={!past}
                    onClick={() => setSearchParams({}, { replace: true })}
                >
                    {texts.resident.current}
                </button>
                <button
                    type="button"
                    className={`ios-tab${past ? ' is-active' : ''}`}
                    role="tab"
                    aria-selected={past}
                    onClick={() => setSearchParams({ tab: 'past' }, { replace: true })}
                >
                    {texts.resident.past}
                </button>
            </nav>
            <DelayedSkeleton loading={list.isPending} />
            {list.isError && <ErrorState error={list.error} onRetry={() => void list.refetch()} />}
            {list.data && requests.length === 0 && !list.isError && (
                <EmptyState text={past ? texts.resident.emptyPast : texts.resident.empty} />
            )}
            {requests.map((item) => (
                <RequestRow
                    key={item.id}
                    item={item}
                    onOpen={(id) => navigate(`/requests/${id}`)}
                />
            ))}
            {list.hasNextPage && !list.isError && (
                <Button
                    variant="secondary"
                    size="medium"
                    loading={list.isFetchingNextPage}
                    onClick={() => void list.fetchNextPage()}
                >
                    {texts.queue.loadMore}
                </Button>
            )}
        </Screen>
    );
}
