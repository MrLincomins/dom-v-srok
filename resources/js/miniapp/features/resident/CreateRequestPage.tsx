import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router';
import { Button, CellSimple } from '@maxhub/max-ui';
import { categoryLeaves, listCategories } from '@/api/catalog';
import { createRequest } from '@/api/requests';
import { useAuth } from '@/app/authContext';
import { texts } from '@/app/texts';
import { CompactNote } from '@/components/CompactNote';
import { DelayedSkeleton } from '@/components/DelayedSkeleton';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { Notice } from '@/components/Notice';
import { RequestSection } from '@/components/RequestSection';
import { Screen } from '@/components/Screen';
import { MutationError } from '@/features/request/MutationError';

export function CreateRequestPage() {
    const navigate = useNavigate();
    const client = useQueryClient();
    const { user } = useAuth();
    const catalog = useQuery({
        queryKey: ['catalog', 'categories'],
        queryFn: ({ signal }) => listCategories(signal),
    });
    const [categoryId, setCategoryId] = useState<number | null>(null);
    const [description, setDescription] = useState('');
    const [notice, setNotice] = useState<string | null>(null);
    const leaves = useMemo(() => categoryLeaves(catalog.data ?? []), [catalog.data]);
    const selected = leaves.find((item) => item.id === categoryId) ?? null;
    const create = useMutation({
        mutationFn: createRequest,
        onSuccess: (card) => {
            void client.invalidateQueries({ queryKey: ['my-requests'] });
            setNotice(texts.resident.created);
            navigate(`/requests/${card.id}`, { replace: true });
        },
    });

    if (!user?.house) {
        return (
            <Screen title={texts.resident.create} backTo="/">
                <EmptyState text={texts.resident.noHouse} />
            </Screen>
        );
    }

    return (
        <Screen
            title={texts.resident.create}
            backTo="/"
            contentClassName="flex min-w-0 flex-col gap-24"
        >
            <DelayedSkeleton loading={catalog.isPending} rows={4} />
            {catalog.isError && (
                <ErrorState error={catalog.error} onRetry={() => void catalog.refetch()} />
            )}
            {catalog.data && (
                <>
                    <RequestSection title={texts.resident.category}>
                        {leaves.map((item) => (
                            <CellSimple
                                key={item.id}
                                title={item.name}
                                subtitle={item.is_emergency ? texts.resident.emergency : item.advice ?? undefined}
                                onClick={() => setCategoryId(item.id)}
                                after={item.id === categoryId ? '✓' : undefined}
                            />
                        ))}
                    </RequestSection>
                    {selected?.is_emergency ? (
                        <RequestSection>
                            <CellSimple
                                title={texts.organization.phoneAds}
                                subtitle={user.organization?.phone_ads ?? ''}
                            />
                        </RequestSection>
                    ) : (
                        <div className="flex min-w-0 flex-col gap-8">
                            <CompactNote
                                label={texts.resident.description}
                                placeholder={texts.resident.description}
                                value={description}
                                onChange={(event) => setDescription(event.target.value)}
                            />
                            <Button
                                size="medium"
                                variant="primary"
                                disabled={categoryId === null || description.trim() === '' || create.isPending}
                                loading={create.isPending}
                                onClick={() =>
                                    create.mutate({
                                        category_id: categoryId as number,
                                        description: description.trim(),
                                    })
                                }
                            >
                                {texts.resident.submit}
                            </Button>
                            <MutationError error={create.error} />
                        </div>
                    )}
                </>
            )}
            <Notice text={notice} onGone={() => setNotice(null)} />
        </Screen>
    );
}
