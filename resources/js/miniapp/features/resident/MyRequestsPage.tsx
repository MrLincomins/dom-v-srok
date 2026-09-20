import { useMemo } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router';
import { Button } from '@maxhub/max-ui';
import { myRequests } from '@/api/requests';
import { texts } from '@/app/texts';
import { DelayedSkeleton } from '@/components/DelayedSkeleton';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { RequestRow } from '@/components/RequestRow';
import { Screen } from '@/components/Screen';
import { UserContextBar } from '@/components/UserContextBar';

export function MyRequestsPage({ backTo }: { backTo?: string }) {
    const navigate = useNavigate();
    const list = useInfiniteQuery({
        queryKey: ['my-requests'],
        queryFn: ({ signal, pageParam }) => myRequests(pageParam, signal),
        initialPageParam: 1,
        getNextPageParam: (lastPage) =>
            lastPage.meta.current_page < lastPage.meta.last_page ? lastPage.meta.current_page + 1 : undefined,
    });
    const pages = list.data?.pages;
    const requests = useMemo(() => pages?.flatMap((page) => page.data) ?? [], [pages]);

    return (
        <Screen
            title={texts.resident.title}
            titleLevel={2}
            backTo={backTo}
            contentClassName="flex min-w-0 flex-col gap-12"
        >
            {!backTo && <UserContextBar />}
            <DelayedSkeleton loading={list.isPending} />
            {list.isError && <ErrorState error={list.error} onRetry={() => void list.refetch()} />}
            {list.data && requests.length === 0 && !list.isError && (
                <EmptyState text={texts.resident.empty} />
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
                    size="large"
                    stretched
                    loading={list.isFetchingNextPage}
                    onClick={() => void list.fetchNextPage()}
                >
                    {texts.queue.loadMore}
                </Button>
            )}
        </Screen>
    );
}
