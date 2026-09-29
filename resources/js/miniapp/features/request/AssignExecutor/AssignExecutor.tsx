import { useQuery } from '@tanstack/react-query';
import { CellSimple, Typography } from '@maxhub/max-ui';
import { listExecutors } from '@/api/organization';
import { texts } from '@/app/texts';
import { ErrorState } from '@/components/ErrorState';
import { InlineLoader } from '@/components/LineLoader';
import { RequestSection } from '@/components/RequestSection';
import { MutationError } from '@/features/request/MutationError';
import type { StaffRequestActionMutations } from '@/features/request/useRequest';

export function AssignExecutor({
    currentName,
    action,
    disabled,
    onAssigned,
}: {
    currentName?: string | null;
    action: StaffRequestActionMutations['assign'];
    disabled: boolean;
    onAssigned?: () => void;
}) {
    const executors = useQuery({
        queryKey: ['organization', 'executors'],
        queryFn: ({ signal }) => listExecutors(signal),
    });

    return (
        <div className="flex min-w-0 flex-col gap-8">
            <RequestSection title={texts.request.assign}>
                {currentName ? (
                    <CellSimple
                        separator
                        title={texts.request.executor}
                        subtitle={currentName}
                    />
                ) : null}
                {!executors.data && executors.isPending ? <InlineLoader /> : null}
                {!executors.data && executors.isError ? (
                    <ErrorState
                        error={executors.error}
                        failureCount={executors.failureCount}
                        onRetry={() => void executors.refetch()}
                    />
                ) : null}
                {(executors.data ?? []).map((executor) => (
                    <CellSimple
                        key={executor.id}
                        separator
                        title={executor.name}
                        subtitle={[executor.specialty, executor.phone].filter(Boolean).join(' · ') || undefined}
                        disabled={disabled || action.isPending}
                        after={<span className="assign-pick">{texts.request.assignPick}</span>}
                        onClick={() => action.mutate(executor.id, { onSuccess: onAssigned })}
                    />
                ))}
            </RequestSection>
            {executors.data?.length === 0 && (
                <Typography.Body variant="small" className="px-16 text-muted">
                    {texts.request.assignEmpty}
                </Typography.Body>
            )}
            <MutationError error={action.error} />
        </div>
    );
}
