import { useMemo } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router';
import { Button, CellSimple } from '@maxhub/max-ui';
import { myRequests } from '@/api/requests';
import { texts } from '@/app/texts';
import { DeadlineChip } from '@/components/DeadlineChip';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { ListSkeleton } from '@/components/ListSkeleton';
import { LogoutButton } from '@/components/LogoutButton';
import { Screen } from '@/components/Screen';
import { StatusChip } from '@/components/StatusChip';

export function MyRequestsPage() {
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
        <Screen title={texts.resident.title} contentClassName="flex flex-col gap-12" right={<LogoutButton />}>
            {list.isPending && <ListSkeleton />}
            {list.isError && <ErrorState error={list.error} onRetry={() => void list.refetch()} />}
            {list.data && requests.length === 0 && !list.isError && (
                <EmptyState text={texts.resident.empty} />
            )}
            {requests.length > 0 && !list.isError && (
                <ul className="enter-list flex flex-col gap-12" aria-busy={list.isFetching}>
                    {requests.map((item) => (
                        <li key={item.id}>
                            <div className="app-card interactive-card overflow-hidden">
                                <CellSimple
                                    surface="island"
                                    showChevron
                                    onClick={() => navigate(`/requests/${item.id}`)}
                                    overline={
                                        <span className="flex flex-wrap gap-8">
                                            <StatusChip
                                                status={item.status}
                                                overdue={item.is_overdue}
                                                label={item.status_label}
                                            />
                                            <DeadlineChip
                                                deadline={item.deadline_fix_at}
                                                closed={
                                                    item.status === 'confirmed' ||
                                                    item.status === 'redirected'
                                                }
                                            />
                                        </span>
                                    }
                                    title={`№ ${item.id} · ${item.category}`}
                                    subtitle={`${item.address} · ${item.responsible_name}`}
                                />
                            </div>
                        </li>
                    ))}
                </ul>
            )}
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
