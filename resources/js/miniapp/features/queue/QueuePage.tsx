import { memo, useCallback, useMemo, useState, type KeyboardEvent } from 'react';
import { useNavigate, useSearchParams } from 'react-router';
import { Button, CellSimple, Counter } from '@maxhub/max-ui';
import { texts } from '@/app/texts';
import { DeadlineChip } from '@/components/DeadlineChip';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { ListSkeleton } from '@/components/ListSkeleton';
import { LogoutButton } from '@/components/LogoutButton';
import { Screen } from '@/components/Screen';
import { StatusChip } from '@/components/StatusChip';
import type { RequestListItem } from '@/api/types';
import { QueueSearch } from './QueueSearch';
import { useQueue, type QueueTab } from './useQueue';

const TABS: QueueTab[] = ['new', 'in_progress', 'overdue', 'closed'];

const StableCounter = memo(function StableCounter({
    value,
    variant,
}: {
    value: number;
    variant: 'attention' | 'default';
}) {
    return <Counter value={value} variant={variant} />;
});

export function QueuePage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const requestedTab = searchParams.get('tab');
    const tab = isQueueTab(requestedTab) ? requestedTab : 'new';
    const [search, setSearch] = useState('');
    const setTab = useCallback(
        (nextTab: QueueTab) => {
            setSearchParams(
                (current) => {
                    if (nextTab === 'new') current.delete('tab');
                    else current.set('tab', nextTab);
                    return current;
                },
                { replace: true },
            );
        },
        [setSearchParams],
    );
    const queue = useQueue(tab, search);

    const pages = queue.data?.pages;
    const items = useMemo(() => pages?.flatMap((page) => page.data) ?? [], [pages]);
    const counters = pages?.[0]?.meta.counters;
    const selectAdjacentTab = useCallback(
        (event: KeyboardEvent, current: QueueTab) => {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
            event.preventDefault();
            const offset = event.key === 'ArrowRight' ? 1 : -1;
            const next = TABS[(TABS.indexOf(current) + offset + TABS.length) % TABS.length];
            if (!next) return;
            setTab(next);
            document.getElementById(`queue-tab-${next}`)?.focus();
        },
        [setTab],
    );

    return (
        <Screen
            title={texts.queue.title}
            backTo="/"
            contentClassName="flex flex-col gap-12"
            right={<LogoutButton />}
        >
            <section className="enter flex flex-col gap-12">
                <QueueSearch onSearch={setSearch} />

                <nav
                    className="flex gap-8 overflow-x-auto overscroll-x-contain rounded-16 bg-page-secondary p-6 scroll-px-12 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    aria-label="Вкладки очереди"
                    role="tablist"
                >
                    {TABS.map((t) => (
                        <Button
                            key={t}
                            size="small"
                            variant={t === tab ? 'primary' : 'secondary'}
                            className="shrink-0 whitespace-nowrap"
                            onClick={() => setTab(t)}
                            onKeyDown={(event) => selectAdjacentTab(event, t)}
                            role="tab"
                            id={`queue-tab-${t}`}
                            tabIndex={t === tab ? 0 : -1}
                            aria-selected={t === tab}
                            aria-controls="queue-panel"
                            indicator={
                                counters && counters[t] > 0 ? (
                                    <StableCounter
                                        value={counters[t]}
                                        variant={t === 'overdue' ? 'attention' : 'default'}
                                    />
                                ) : undefined
                            }
                        >
                            {texts.queue.tabs[t]}
                        </Button>
                    ))}
                </nav>
            </section>

            <div
                id="queue-panel"
                role="tabpanel"
                aria-labelledby={`queue-tab-${tab}`}
                className="flex flex-col gap-12"
            >
                {(queue.isPending || queue.isPlaceholderData) && <ListSkeleton rows={3} />}
                {queue.isError && !queue.isPlaceholderData && (
                    <ErrorState error={queue.error} onRetry={() => void queue.refetch()} />
                )}
                {queue.data && items.length === 0 && !queue.isError && !queue.isPlaceholderData && (
                    <EmptyState text={texts.queue.empty[tab]} />
                )}
                {items.length > 0 && !queue.isError && !queue.isPlaceholderData && (
                    <ul className="enter-list flex flex-col gap-12" aria-busy={queue.isFetching}>
                        {items.map((item) => (
                            <li key={item.id}>
                                <QueueRow item={item} />
                            </li>
                        ))}
                    </ul>
                )}
                {queue.hasNextPage && !queue.isError && !queue.isPlaceholderData && (
                    <div>
                        <Button
                            variant="secondary"
                            size="large"
                            stretched
                            loading={queue.isFetchingNextPage}
                            onClick={() => void queue.fetchNextPage()}
                        >
                            {texts.queue.loadMore}
                        </Button>
                    </div>
                )}
            </div>
        </Screen>
    );
}

function QueueRow({ item }: { item: RequestListItem }) {
    const navigate = useNavigate();
    const closed = item.status === 'confirmed' || item.status === 'redirected';
    const place = [
        item.address,
        item.entrance ? `подъезд ${item.entrance}` : null,
        item.flat ? `кв. ${item.flat}` : null,
    ]
        .filter(Boolean)
        .join(', ');

    return (
        <div
            className={`app-card interactive-card overflow-hidden ${item.is_overdue ? 'ring-2 ring-late/30' : ''}`}
        >
            <CellSimple
                surface="island"
                showChevron
                onClick={() => navigate(`/requests/${item.id}`)}
                overline={
                    <span className="flex flex-wrap items-center gap-8">
                        <StatusChip
                            status={item.status}
                            overdue={item.is_overdue}
                            label={item.status_label}
                        />
                        <DeadlineChip deadline={item.deadline_fix_at} closed={closed} />
                        {item.participants_count > 0 && (
                            <span className="text-[13px] text-muted">
                                {texts.queue.neighbors(item.participants_count)}
                            </span>
                        )}
                    </span>
                }
                title={
                    <span className="tabular font-semibold">
                        № {item.id} · {item.category}
                    </span>
                }
                subtitle={
                    <span className="leading-relaxed">
                        {place}
                        <br />
                        {item.responsible_name}
                        {item.executor ? ` · ${item.executor.name}` : ''}
                    </span>
                }
            />
        </div>
    );
}

function isQueueTab(value: string | null): value is QueueTab {
    return value === 'new' || value === 'in_progress' || value === 'overdue' || value === 'closed';
}
