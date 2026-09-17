import { useState } from 'react';
import { useNavigate } from 'react-router';
import { Button, CellSimple, Counter, Input } from '@maxhub/max-ui';
import { useAuth } from '@/app/auth';
import { texts } from '@/app/texts';
import { DeadlineChip } from '@/components/DeadlineChip';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { ListSkeleton } from '@/components/ListSkeleton';
import { PageHeader } from '@/components/PageHeader';
import { StatusChip } from '@/components/StatusChip';
import type { RequestListItem } from '@/api/types';
import { useQueue, type QueueTab } from './useQueue';

const TABS: QueueTab[] = ['new', 'in_progress', 'overdue', 'closed'];

/** очередь диспетчера: вкладки со счётчиками, поиск, список по сроку. состояния: скелет, пусто, ошибка, данные */
export function QueuePage() {
    const { user } = useAuth();
    const [tab, setTab] = useState<QueueTab>('new');
    const [search, setSearch] = useState('');
    const queue = useQueue(tab, search);

    if (user && user.role === 'resident') {
        return <ErrorState message={texts.auth.notStaff} fullscreen />;
    }

    const counters = queue.data?.meta.counters;

    return (
        <main className="pb-6">
            <PageHeader title={texts.queue.title} />

            <div className="px-4 pb-2">
                <Input
                    placeholder={texts.queue.search}
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    withClearButton
                    inputMode="search"
                />
            </div>

            <nav className="flex gap-2 overflow-x-auto px-4 pb-3" aria-label="Вкладки очереди">
                {TABS.map((t) => (
                    <Button
                        key={t}
                        size="small"
                        variant={t === tab ? 'primary' : 'secondary'}
                        onClick={() => setTab(t)}
                        aria-pressed={t === tab}
                        indicator={
                            counters && counters[t] > 0 ? (
                                <Counter
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

            {queue.isPending && <ListSkeleton />}
            {queue.isError && <ErrorState error={queue.error} onRetry={() => void queue.refetch()} />}
            {queue.data && queue.data.data.length === 0 && <EmptyState text={texts.queue.empty[tab]} />}
            {queue.data && queue.data.data.length > 0 && (
                <ul className="flex flex-col gap-2 px-4" aria-busy={queue.isFetching}>
                    {queue.data.data.map((item) => (
                        <li key={item.id}>
                            <QueueRow item={item} />
                        </li>
                    ))}
                </ul>
            )}
        </main>
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
        <CellSimple
            surface="island"
            showChevron
            onClick={() => navigate(`/requests/${item.id}`)}
            overline={
                <span className="flex flex-wrap items-center gap-2">
                    <StatusChip status={item.status} overdue={item.is_overdue} />
                    <DeadlineChip deadline={item.deadline_fix_at} closed={closed} />
                    {item.participants_count > 0 && (
                        <span className="text-[13px] text-muted">
                            {texts.queue.neighbors(item.participants_count)}
                        </span>
                    )}
                </span>
            }
            title={
                <span className="tabular">
                    № {item.id} · {item.category}
                </span>
            }
            subtitle={
                <span>
                    {place}
                    <br />
                    {item.responsible_name}
                    {item.executor ? ` · ${item.executor.name}` : ''}
                </span>
            }
        />
    );
}
