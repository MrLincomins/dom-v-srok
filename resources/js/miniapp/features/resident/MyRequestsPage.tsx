import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router';
import { CellSimple } from '@maxhub/max-ui';
import { myRequests } from '@/api/requests';
import { texts } from '@/app/texts';
import { DeadlineChip } from '@/components/DeadlineChip';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { ListSkeleton } from '@/components/ListSkeleton';
import { PageHeader } from '@/components/PageHeader';
import { StatusChip } from '@/components/StatusChip';

/** мои заявки для жителя, пока просто список с переходом в карточку */
export function MyRequestsPage() {
    const navigate = useNavigate();
    const list = useQuery({ queryKey: ['my-requests'], queryFn: ({ signal }) => myRequests(signal) });

    return (
        <main className="pb-6">
            <PageHeader title="Мои заявки" />
            {list.isPending && <ListSkeleton />}
            {list.isError && <ErrorState error={list.error} onRetry={() => void list.refetch()} />}
            {list.data && list.data.data.length === 0 && (
                <EmptyState text="У вас пока нет заявок. Нажмите «Сообщить о проблеме» в боте." />
            )}
            {list.data && list.data.data.length > 0 && (
                <ul className="flex flex-col gap-2 px-4">
                    {list.data.data.map((item) => (
                        <li key={item.id}>
                            <CellSimple
                                surface="island"
                                showChevron
                                onClick={() => navigate(`/requests/${item.id}`)}
                                overline={
                                    <span className="flex gap-2">
                                        <StatusChip status={item.status} overdue={item.is_overdue} />
                                        <DeadlineChip
                                            deadline={item.deadline_fix_at}
                                            closed={
                                                item.status === 'confirmed' || item.status === 'redirected'
                                            }
                                        />
                                    </span>
                                }
                                title={`№ ${item.id} · ${item.category}`}
                                subtitle={item.responsible_name}
                            />
                        </li>
                    ))}
                </ul>
            )}
            <p className="sr-only">{texts.queue.title}</p>
        </main>
    );
}
